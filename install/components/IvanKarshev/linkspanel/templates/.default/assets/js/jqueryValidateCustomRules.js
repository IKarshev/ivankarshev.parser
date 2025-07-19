$(function(){
    // Правило валидации телефона
    jQuery.validator.addMethod("checkPhone", function(value, element, param) {
        let newValue = value.replace(/[^0-9]/g, '');
        return (newValue.length == param);
    }, $.validator.format("Please enter exactly {0} characters."));

    jQuery.validator.addMethod("require_from_group", function(value, element, options) {
        var validator = this;
        var selector = options[1];
        var validOrNot = $(selector, element.form).filter(function() {
            return validator.elementValue(this);
        }).length >= options[0];
    
        if(!$(element).data('being_validated')) {
            var fields = $(selector, element.form);
            fields.data('being_validated', true);
            fields.valid();
            fields.data('being_validated', false);
        }
        return validOrNot;
    }, $.validator.format("Please fill at least {0} of these fields."));
})