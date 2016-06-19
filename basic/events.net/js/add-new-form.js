var AddNewEventForm = {
    form: $('#add-new-event-form'),
    iconsContainer: $('#icons-container'),
    icons:$('.add-new-icon'),
    iconInput: $('#icon-input'),
    iconsDir: '/icon/',
    selectedIcon: $('#selected-icon'),

    init: function(){
        var self = this;
        self.form.find('#icon-status').click(function(){
            if(!self.iconsContainer.hasClass('opened')){
                self.iconsContainer.addClass('opened');
                self.iconsContainer.show();
            }else{
                self.closeIconsWrapper();
            }
        });

        self.selectIconEvent();
    },

    selectIconEvent: function(){
        var self = this;
        self.icons.click(function(){
            self.iconInput.val($(this).data('value'));
            self.selectedIcon.attr('src', self.iconsDir + $(this).data('value'));
            self.closeIconsWrapper();
        });
    },

    closeIconsWrapper: function(){
        var self = this;
        self.iconsContainer.removeClass('opened');
        self.iconsContainer.hide();
    }
};

$(function(){
    AddNewEventForm.init();
});
