var forgeEvents = {
    init : function() {
        forgeEvents.seatPlan();
        forgeEvents.searchParticipants();
    },

    searchParticipants : function() {
        var timeout = false;
        $("input#searchParticipants").on('input', function() {
            clearTimeout(timeout);
            var searchString = $(this).val();
            searchString = searchString.toLowerCase();
            $(".seatplan").removeClass('searchActive');
            timeout = setTimeout(function() {
                $(".seatplan").find(".cell").each(function() {
                    if(searchString.length > 0 && 
                        typeof($(this).attr('data-cell-user')) == 'string' 
                        && $(this).attr('data-cell-user').toLowerCase().indexOf(searchString) >= 0 ) {
                        $(this).addClass('highlight');
                        $(this).closest('.seatplan').addClass('searchActive');
                    } else {
                        $(this).removeClass('highlight');
                    }
                });

                $("#participants .compact-infobox").each(function() {
                    if( $(this).find("h4").text().toLowerCase().indexOf(searchString) >= 0 &&
                        searchString.length > 0) {
                        $(this).closest('.seatplan').addClass('searchActive');
                        $(this).addClass('highlight');
                    } else {
                        $(this).removeClass('highlight');
                    }
                });

            }, 400);
        });
    },

    seatPlan : function() {
        $(".seatplan").each(function() {
            var parent = $(this).parent();
            var plan = $(this);
            var apiUrl = $(this).data('api');
            var eventId = $(this).data('event');
            if(! apiUrl && ! eventId) {
                return;
            }
            $(this).find(".s-row").each(function() {
                var row = $(this).data('row-id');
                $(this).find(".cell").each(function() {
                    $(this).on("click", function() {
                        plan.addClass("loading");
                        var seat = $(this).data('cell-id');
                        var seatReservation = $("input[name='forge-events-user-to-set']");
                        var reservationRequest = 'none';
                        var isAdmin = true;
                        seatReservation.each(function() {
                            isAdmin = false;
                            if($(this).is(':checked')) {
                                reservationRequest = $(this).data('user-id');
                            }
                        });
                        if(isAdmin) {
                            reservationRequest = 'admin';
                        }

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