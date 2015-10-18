var EventsList = {
    eventsCount: 2,
    changeImageWrapperHeight: function() {
        var eventElements = $('.event-element');
        $.each(eventElements, function (key, element) {
            var height = $(element).outerHeight();
            $(element).find('.people-image-wrapper').outerHeight(height + 10);
        });
    }
};