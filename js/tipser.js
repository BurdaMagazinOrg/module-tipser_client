const TipserProductView = Backbone.View.extend({
  initialize: function(options) {
    this.model = new Backbone.Model({
      productId: null,
      pageOpen: false,
    });

    this.openProductDialog = options.openProductDialog;
  },
  events: {
    click: 'handleClick',
  },
  handleClick: function(e) {
    const productId = e.currentTarget.getAttribute('data-product-id');
    e.preventDefault();
    this.model.set('productId', productId);
    this.openProductDialog(productId);
    return !e.currentTarget.getAttribute('data-external-url');
  },
});

Drupal.behaviors.instyleInfiniteTipser = {
  userid: drupalSettings.tipser.userid,
  env: drupalSettings.tipser.env,
  tipserSDK: null,
  tipserIconViewsArr: [],
  thankYouRedirectUrl: null,
  initialized: false,
  attach: function(context) {
    const tipserSelector = '[data-provider="tipser"]';
    jQuery(tipserSelector, context)
      .addBack(tipserSelector)
      .each(
        function(index, $element) {
          /* eslint-disable no-new */
          new TipserProductView({
            el: $element,
            openProductDialog: this.openProductDialog.bind(this),
          });
        }.bind(this)
      );
    this.initTipser();
  },

  handleTipserTracking: function(trackingObject) {
    trackingObject = Object.assign({ event: 'tipserTracking' }, trackingObject);

    if (typeof window.dataLayer !== 'undefined') {
      window.dataLayer.push(trackingObject);
    }
  },

  initTipser: function(callback) {
    // only initialize once
    if (this.initialized) {
      return;
    }
    this.initialized = true;

    const tipserConfig = {
      env: this.env,
      userid: this.userid,
      primaryColor: '#222222',
      gtl: {
        hideText: 'false',
      },
      defaultLang: 'de',
      market: 'DE',
      shop: {
        listMenuPosition: 'left',
        hideFooter: 0,
        imgSize: 2,
      },
      tab: {
        hide: 1,
      },
      modalUi: {
        hideSearchIcon: true,
        hideFavouritesIcon: true,
        hideCartIcon: true,
        hideMoreIcon: true,
        hideSimilarProducts: true,
        useCustomCss: true,
      },
      redirectToQueryString: true,
    };

    if (window.location.pathname.indexOf('/tipser-product/') === 0) {
      this.thankYouRedirectUrl = new URL(window.location.href).searchParams.get(
        'article'
      );
    }

    /* global TipserSDK */
    this.tipserSDK = new TipserSDK(this.userid, tipserConfig);
    this.tipserSDK.addDialogClosedListener(this.closeDialog.bind(this));
    this.tipserSDK.addTrackEventListener(this.handleTipserTracking);
    this.tipserSDK.addThankYouPageClosedListener(
      this.onThankYouOverlayClose.bind(this)
    );
    this.getCurrentCartSize();
    if (callback) callback();
  },

  onToggleProductDialog: function(model) {
    if (model.get('pageOpen') === true) {
      this.openProductDialog(model.get('productId'));
    } else {
      this.closeDialog();
    }
  },

  onToggleShoppingCartOverlay: function(model) {
    if (model.get('openShoppingCartOverlay') === true) {
      this.openPurchaseDialog();
    } else {
      this.closeDialog();
    }
  },

  onThankYouOverlayClose: function() {
    if (this.thankYouRedirectUrl) {
      window.open(this.thankYouRedirectUrl, '_self');
    }
  },

  closeDialog: function() {
    this.tipserSDK.closeDialog();
    this.getCurrentCartSize();
    /**
     * Bug in blazy or tipser sdk causes lazy loading to fail
     * reinitializing blazy
     */
    !!window.Blazy && new window.Blazy();
  },

  openProductDialog: function(productId) {
    this.tipserSDK.openProductDialog(productId);
  },

  openPurchaseDialog: function() {
    this.tipserSDK.openPurchaseDialog();
  },

  getCurrentCartSize: function() {
    this.tipserSDK.getCurrentCartSize().then(function(cartSize) {
      window.dispatchEvent(
        new CustomEvent('tipser_cart_changed', {
          detail: { cartSize: cartSize },
        })
      );
    });
  },

  openTipserProductDetailPage: function(productId) {
    this.openProductDialog(productId);
  },
};
