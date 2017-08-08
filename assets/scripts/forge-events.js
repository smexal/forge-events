var forgeEvents = {
    init : function() {
        forgeEvents.seatPlan();
    },

    seatPlan : function() {
        $(".seatplan").each(function() {
            var parent = $(this).parent();
            var plan = $(this);
            var apiUrl = $(this).data('api');
            var eventId = $(this).data('event');
            $(this).find(".s-row").each(function() {
                var row = $(this).data('row-id');
                $(this).find(".cell").each(function() {
                    $(this).on("click", function() {
                        plan.addClass("loading");
                        var seat = $(this).data('cell-id');
                        var seatReservation = $("input[name='forge-events-user-to-set']");
                        var reservationRequest = 'none';
                        seatReservation.each(function() {
                            if($(this).is(':checked')) {
                                reservationRequest = $(this).data('user-id');
                            }
                        });

                        $.ajax({
                            method: "POST",
                            url: apiUrl,
                            data: {
                                'x' : seat,
                                'y' : row,
                                'event' : eventId,
                                'reservation' : reservationRequest
                            }
                        }).done(function(data) {
                            plan.remove();
                            parent.append(data.plan);
                            $(document).trigger("ajaxReload");
                        })
                    });
                });
            })
        })
    }
};

$(document).ready(forgeEvents.init);
$(document).on("ajaxReload", forgeEvents.init);