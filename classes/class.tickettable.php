<?

class TicketTable {
    private $event;
    public $tableId = "ticketTable";

    public function __construct($event) {
        $this->event = $event;
    }


    public function draw() {
        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "table", array(
            'id' => $this->tableId,
            'th' => array(
                Utils::tableCell(i('id', 'forge-events')),
                Utils::tableCell(i('Order Date', 'forge-events')),
                Utils::tableCell(i('User', 'forge-events')),
                Utils::tableCell(i('Typ', 'forge-events')),
                Utils::tableCell(i('Seat', 'forge-events')),
                Utils::tableCell(i('Status', 'forge-events')),
                Utils::tableCell(i('Total Amount', 'forge-events')),
                Utils::tableCell(i('Items', 'forge-events')),
                Utils::tableCell(i('Actions'))
            ),
            'td' => $this->getOrderRows()
        ));
    }

    public function removeDrafts() {
        $orders = Payment::getOrders($this->event);
        foreach($orders as $order) {
            if($order->data['status'] == 'draft') {
                Payment::deleteOrder($order->data['id']);
            }
        }
    }

    private function getOrderRows() {
        $orders = Payment::getOrders($this->event);
        $ordersEnriched = array();
        foreach($orders as $order) {
            $user = new User($order->data['user']);

            if($order->data['status'] == 'success') {
                $stepSeat = new SignupStepSeat($this->event);
                $seat = $stepSeat->getUserSeat($order->data['user']);
            } else {
                $seat = '';
            }

            array_push($ordersEnriched, array(
                Utils::tableCell($order->data['id']),
                Utils::tableCell(Utils::dateFormat($order->getDate(), true)),
                Utils::tableCell($user->get('username')),
                Utils::tableCell($order->data['payment_type']),
                Utils::tableCell($seat),
                Utils::tableCell($order->data['status']),
                Utils::tableCell(Utils::formatAmount($order->data['price'])),
                Utils::tableCell($order->getItemAmount()),
                Utils::tableCell($this->actions($order))
            ));
        }
        return $ordersEnriched;
    }

    private function actions($order) {
        if(!Auth::allowed("manage.forge-events.ticket-status.edit")) {
            return;
        }
        $deleteUrl = Utils::getUrl(
            ['api', 'forge-events-tickets', 'delete-order'],
            true,
            [
                'event' => $this->event,
                'order' => $order->data['id']
            ]
        );

        $acceptUrl = Utils::getUrl(
            ['api', 'forge-events-tickets', 'accept-order'],
            true,
            [
                'event' => $this->event,
                'order' => $order->data['id']
            ]
        );

        $actions = [
            'actions' => []
        ];

        if($order->data['status'] != 'success') {
            array_push($actions['actions'], [
                "url" => $acceptUrl,
                "icon" => "ok",
                "name" => i('Accept order', 'forge-events'),
                "ajax" => true,
                "confirm" => false
            ]);
        }

        array_push($actions['actions'], [
            "url" => $deleteUrl,
            "icon" => "trash",
            "name" => i('Delete order', 'forge-events'),
            "ajax" => true,
            "confirm" => false
        ]);

        return App::instance()->render(CORE_TEMPLATE_DIR."assets/", "table.actions", $actions);
    }
}

?>