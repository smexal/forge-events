<?php

namespace Forge\Modules\ForgeEvents;

use \Forge\Modules\ForgeQR\ForgeQR;
use \Forge\Core\Abstracts\View;
use \Forge\Core\App\App;
use \Forge\Core\Classes\CollectionItem;
use \Forge\Core\Classes\Media;
use \Forge\Core\Classes\Settings;
use \Forge\Core\Classes\User;
use \Forge\Core\Classes\Utils;
use \Forge\Modules\ForgeEvents\Seatplan;
use \Forge\Modules\ForgeFPDF\PDF;
use \Forge\Modules\ForgePayment\Payment;

class TicketprintView extends View {
    public $name = 'fe-ticket-print';
    public $standalone = true;
    private $pdf = null;

    private $fontSizeLabel = 9;
    private $fontSizeValue = 12;

    public function content($parts = array()) {
        if(count($parts) == 0) {
            echo 'false';
            exit();
        }

        $order = Payment::getOrder($parts[0]);
        $isOwner = false;
        if($order->data['user'] !== App::instance()->user->get('id')) {
            $isPartOwner = false;
            foreach($order->data['paymentMeta']->{'items'} as $item) {
                if(App::instance()->user->get('id') == $item->user) {
                    $isPartOwner = true;
                }
            }
            if(! $isPartOwner) {
                echo 'nope dope..';
                exit();
            }
        } else {
            $isOwner = true;
        }
        if($order->data['status'] !== 'success') {
            echo 'nope';
            exit();
        }

        if(! App::instance()->mm->isActive('forge-fpdf')) {
            echo 'no fpdf mobule';
            exit();
        }

        $this->pdf = new PDF();

        $orderMeta = json_decode( $order->data['meta'] );
        foreach($orderMeta->items as $item) {
            if($isOwner) {
                $this->addTicketPage($item, $order);
            } else {
                if($item->user == App::instance()->user->get('id')) {
                    $this->addTicketPage($item, $order);
                }
            }
        }

        $this->pdf->file->Output();
        
    }

    private function addTicketPage($item, $order) {
        $titleCollection = $item->collection;
        $titleCollection = new CollectionItem($titleCollection);

        $this->pdf->file->AddPage();
        $this->pdf->file->SetTextColor(10, 10, 10);

        $offsetLeft = 20;
        $outerRight = 170;
        $offsetY = 80;

        $logo = new Media(Settings::get('forge-pdf-logo'));

        $this->pdf->file->SetX(0);
        if($logo->getAbsolutePath() != '') {
            $this->pdf->file->Image($logo->getAbsolutePath(), $offsetLeft, 20, 30, 0, 'PNG');
        }
        $this->pdf->file->SetFont('Arial','B',16);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell(
            160, 
            10,
            utf8_decode(sprintf(i('Ticket for %s', 'forge-events'), $titleCollection->getMeta('title'))),
            0
        );

        /* QR */
        $get = ['id' => Utils::encodeBase64($order->data['id'])];
        $checkInUrl = Utils::getUrl(['api', 'checkin', 'scan'], true, $get, false, true);
        $image = Utils::getUrl(['fe-qr'], true, ['text' => $checkInUrl], false, true);
        $this->pdf->file->Image($image, 140, 35, 40, 0, 'PNG');

        $this->pdf->file->SetFont('Arial','',10);
        $offsetY+=10;
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell($outerRight, 6, utf8_decode($titleCollection->getMeta('start-date').' - '.$titleCollection->getMeta('end-date')), 0);
        $offsetY+=5;
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell($outerRight, 6, utf8_decode($titleCollection->getMeta('address')), 0);

        $this->pdf->file->SetFont('Arial','',12);
        $this->pdf->file->SetDrawColor(230,230,230);
        $this->pdf->file->SetLineWidth(.3);
            // x1; y1; x2; y2;
        $offsetY+=12;
        $this->pdf->file->Line($offsetLeft, $offsetY, $outerRight, $offsetY);
        
        if($titleCollection->getMeta('disable-seatplan') !== 'on') {
            $seatplan = new Seatplan($item->collection);
        }
        $user = new User($item->user);

        $offsetY+= 4;
        $this->pdf->file->SetFont('Arial','', $this->fontSizeLabel);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell(50, 6, utf8_decode(i('User', 'forge-events')), 0);
        
        $offsetY+= 5;
        $this->pdf->file->SetFont('Arial','', $this->fontSizeValue);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell(50, 6, utf8_decode($user->get('username')).' ('.utf8_decode($user->get('email')).')', 0);

        if($titleCollection->getMeta('disable-seatplan') !== 'on') {
            $offsetY+= 10;
            $this->pdf->file->SetFont('Arial','', $this->fontSizeLabel);
            $this->pdf->file->SetXY($offsetLeft, $offsetY);
            $this->pdf->file->Cell(50, 6, utf8_decode(i('Seat', 'forge-events')), 0);

            $offsetY+= 5;
            $this->pdf->file->SetFont('Arial','', $this->fontSizeValue);
            $this->pdf->file->SetXY($offsetLeft, $offsetY);
            $this->pdf->file->Cell(50, 6, utf8_decode($seatplan->getUserSeat($user->get('id'))), 0);
        }

        $offsetY+= 10;
        $this->pdf->file->SetFont('Arial','', $this->fontSizeLabel);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell(50, 6, utf8_decode(i('Order date / Booking ID', 'forge-events')), 0);


        $offsetY+= 5;
        $this->pdf->file->SetFont('Arial','', $this->fontSizeValue);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $text = Utils::dateFormat($order->data['order_date'], true).' / '.$order->data['id'];
        $this->pdf->file->Cell(50, 6, utf8_decode($text), 0);

        $offsetY+= 3;
        $this->pdf->file->Line($offsetLeft, $offsetY+8, $outerRight, $offsetY+8);

        $offsetY+= 10;
        $this->pdf->file->SetFont('Arial','', $this->fontSizeLabel);
        $this->pdf->file->SetXY($offsetLeft, $offsetY);
        $this->pdf->file->Cell(50, 6, utf8_decode(Settings::get('forge-events-ticket-text-below-facts')), 0);
        
        $this->pdf->file->SetTextColor(160, 160, 160);
        $this->pdf->file->SetFont('Arial','B', 7);
        $this->pdf->file->SetXY($offsetLeft, 270);
        $this->pdf->file->Cell(170, 6, utf8_decode(Settings::get('forge-events-ticket-footer-text')), 0, 1, 'C');
        
    }
}
