var forgeCheckin = {
    init : function() {
        let opts = {
            video: document.getElementById('preview'),
            continuous: false
        };
        let scanner = new Instascan.Scanner(opts);
        scanner.addListener('scan', function (content) {
            forgeCheckin.deactivateScan();
            $.ajax({
                method: "POST",
                url: content,
            }).done(function(data) {
                $('.checkin-feedback').addClass(data.status);
                $('.checkin-feedback .status').text(data.text);
            });
        });

        $('#newscan').click(function() {
            $('.checkin-feedback').removeClass('success');
            $('.checkin-feedback').removeClass('error');
            $('.checkin-feedback').removeClass('unpaid');
            forgeCheckin.activateScan();
        });
    },

    activateScan: function () {
        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                scanner.start(cameras[0]);
            } else {
                $('#messages').text('No cameras found');
            }
        }).catch(function (e) {
            $('#messages').text(e.message);
        });
    },

    deactivateScan: function () {
        scanner.stop(cameras[0]);
    }

};

$(document).ready(forgeCheckin.init);
$(document).on("ajaxReload", forgeCheckin.init);