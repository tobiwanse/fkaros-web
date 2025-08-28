document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    const calendar_list_params = ajax_get_google_calendar_list_params;
    const calendar_events_params = ajax_get_google_calendar_events_params;
    let allEvents = []; // Store all events for the year
    let fetchedYears = new Set(); // Keep track of fetched years

    const calendar = new FullCalendar.Calendar(calendarEl, {
        schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        initialView: window.innerWidth >= 769 ? 'dayGridMonth' : 'listMonth',
        windowResize: function(view) {
            if (window.innerWidth >= 769) {
                calendar.changeView('dayGridMonth');
            } else {
                calendar.changeView('listMonth');
            }
        },
        headerToolbar: {
            left: 'prev,next,today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        contentHeight: 'auto',
        weekNumbers: true,
        fixedWeekCount: false,
        weekText: 'v',
        handleWindowResize: true,
        editable: true,
        droppable: true,
        navLinks: true,
        selectable: true,
        dayMaxEvents: 3,
        displayEventTime: true,
        displayEventEnd: true,
        firstDay: 1,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            //meridiem: false,
            hour12: false
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const startDate = new Date(fetchInfo.startStr);
            const endDate = new Date(fetchInfo.endStr);
            const startYear = startDate.getFullYear();
            const endYear = endDate.getFullYear();

            console.log('View start date:', startDate);
            console.log('View end date:', endDate);

            // Check if we already have events for the specified date range
            const filteredEvents = allEvents.filter(event => {
                return (
                    new Date(event.start) >= new Date(fetchInfo.startStr) &&
                    new Date(event.start) <= new Date(fetchInfo.endStr)
                );
            });

            if (allEvents.length > 0) {
                successCallback(allEvents);
                return;
            }

            // Fetch events for the entire year if not already fetched
            const startOfYear = new Date(startYear, 0, 1).toISOString();
            const endOfYear = new Date(startYear, 11, 31).toISOString();

            jQuery.ajax({
                method: 'POST',
                dataType: 'json',
                url: calendar_events_params.ajax_url,
                data: {
                    _ajax_nonce: calendar_events_params._ajax_nonce,
                    action: calendar_events_params.action,
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr
                },
            })
            .done(function(response) {
                console.log('events', response);
                allEvents = response;

                successCallback(allEvents);
            })
            .fail(function(result) {
                console.log('Error', result);
                failureCallback(result);
            })
            .always(function(result) { });
        },
        eventDidMount: function(info) {
            const now = new Date();
            if (info.event.end < now) {
                info.el.style.opacity = 0.7;
            }
        },
    });
    calendar.render();
});