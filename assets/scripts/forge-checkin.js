var forgeCheckin = {
    init : function() {
        let opts = {
            video: document.getElementById('preview'),
            continuous: true,
            mirror: false,
        };
        let scanner = new Instascan.Scanner(opts);
        scanner.addListener('scan', function (content) {
            forgeCheckin.deactivateScan(scanner);
            $.ajax({
                method: "POST",
                url: content,
            }).done(function(data) {
                $('.checkin-feedback').addClass(data.status);
                $('.checkin-feedback .status').text(data.text);
            });
        });
        forgeCheckin.activateScan(scanner);

        $('#newscan').click(function() {
            $('.checkin-feedback').removeClass('success');
            $('.checkin-feedback').removeClass('error');
            $('.checkin-feedback').removeClass('unpaid');
            forgeCheckin.activateScan(scanner);
        });
    },

    activateScan: function (scanner) {
        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                let camera;
                for(let i = 0; i < cameras.length; i++) {
                    if(cameras[i].name.indexOf('back') !== -1) {
                        camera = cameras[i];
                        break;
                    }
                }
                if(!camera) {
                    camera = cameras[0];
                }
                scanner.start(camera);
            } else {
                $('#messages').text('No cameras found');
            }
        }).catch(function (e) {
            $('#messages').text(e.message);
        });
    },
    deactivateScan: function (scanner) {
        Instascan.Camera.getCameras().then(function (cameras) {
            if (cameras.length > 0) {
                let camera;
                for(let i = 0; i < cameras.length; i++) {
                    if(cameras[i].name.indexOf('back') !== -1) {
                        camera = cameras[i];
                        break;
                    }
                }
                if(!camera) {
                    camera = cameras[0];
                }
                scanner.stop(camera);
            }
        });
    }
};

$(document).ready(forgeCheckin.init);
$(document).on("ajaxReload", forgeCheckin.init);