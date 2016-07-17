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
                        var seat = $(this).data('cell-id');
                        $.ajax({
                            method: "POST",
                            url: apiUrl,
                            data: {
                                'x' : seat,
                                'y' : row,
                                'event' : eventId
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