const TipserProductView = Backbone.View.extend({
  initialize(options) {
    this.model = new Backbone.Model({
      productId: null,
      pageOpen: false,
    });

    this.openProductDialog = options.openProductDialog;
  },
  events: {
    click: 'handleClick',
  },
  handleClick(e) {
    const productId = e.currentTarget.getAttribute('data-product-id');
    this.model.set('productId', productId);
    this.openProductDialog(productId);
  },
});

Drupal.behaviors.instyleInfiniteTipser = {
  userid: '5aa12d639d25800ff0e56fc5',
  tipserSDK: null,
  tipserIconViewsArr: [],
  thankYouRedirectUrl: '/',
  attach(context) {
    jQuery('[data-provider="tipser"]', context).each((index, $element) => {
      /* eslint-disable no-new */
      new TipserProductView({
        el: $element,
        openProductDialog: this.openProductDialog.bind(this),
      });
    });
    this.initTipser();
  },

  handleTipserTracking(trackingObject) {
    trackingObject = Object.assign({ event: 'tipserTracking' }, trackingObject);

    if (typeof window.dataLayer !== 'undefined') {
      window.dataLayer.push(trackingObject);
    }
  },

  initTipser() {
    const tipserConfig = {
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

      /* global TipserSDK */
    this.tipserSDK = new TipserSDK(this.userid, tipserConfig);
    this.tipserSDK.addDialogClosedListener(this.closeDialog.bind(this));
    this.tipserSDK.addTrackEventListener(this.handleTipserTracking);
    this.tipserSDK.addThankYouPageClosedListener(this.onThankYouOverlayClose.bind(this));
    window.dispatchEvent(new CustomEvent('tipser_sdk_loaded', { detail: { tipserSDK: this.tipserSDK } }));
    this.getCurrentCartSize();
  },

  onToggleProductDialog(model) {
    if (model.get('pageOpen') === true) {
      this.openProductDialog(model.get('productId'));
    }
    else {
      this.closeDialog();
    }
  },

  onToggleShoppingCartOverlay(model) {
    if (model.get('openShoppingCartOverlay') === true) {
      this.openPurchaseDialog();
    }
    else {
      this.closeDialog();
    }
  },

  onThankYouOverlayClose() {
    window.open(this.thankYouRedirectUrl, '_self');
  },

  closeDialog() {
    this.tipserSDK.closeDialog();
    this.getCurrentCartSize();
  },

  openProductDialog(productId) {
    this.tipserSDK.openProductDialog(productId);
  },

  openPurchaseDialog() {
    this.tipserSDK.openPurchaseDialog();
  },

  getCurrentCartSize() {
    this.tipserSDK.getCurrentCartSize().then((cartSize) => {
      window.dispatchEvent(new CustomEvent('tipser_cart_changed', { detail: { cartSize } }));
    });
  },

  openTipserProductDetailPage(productId) {
    this.openProductDialog(productId);
  },
};
