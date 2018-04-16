if (typeof Craft.StripeButton === typeof undefined) {
    Craft.StripeButton = {};
}

/**
 * Class Craft.StripeButton.OrderIndex
 */
Craft.StripeButton.OrderIndex = Craft.BaseElementIndex.extend({
    getViewClass: function(mode) {
        switch (mode) {
            case 'table':
                return Craft.StripeButton.OrderTableView;
            default:
                return this.base(mode);
        }
    }
});

// Register the Paypal order index class
Craft.registerElementIndexClass('enupal\\stripe\\elements\\Order', Craft.StripeButton.OrderIndex);