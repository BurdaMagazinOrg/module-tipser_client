import React, { Component } from 'react';
import TipserLayer from './TipserLayer';

class TipserIcon extends Component {
  constructor(props) {
    super(props);
    const { instyleInfiniteTipser } = Drupal.behaviors;
    this.handleTipserCartChanged = this.handleTipserCartChanged.bind(this);
    this.hideOnWishlistOpen = this.hideOnWishlistOpen.bind(this);
    this.handleTipserCartChanged = this.handleTipserCartChanged.bind(this);
    this.handleTipserIconClick = this.handleTipserIconClick.bind(this);
    this.layerDecision = this.layerDecision.bind(this);
    this.toggleLayer = this.toggleLayer.bind(this);
    this.handleOutsideClick = this.handleOutsideClick.bind(this);
    this.button = null;
    this.overlay = null;
    this.state = {
      isLayerVisible: false,
      cartSize: window.localStorage.getItem('cartSize') || 0,
      tipserSDK: {
        openPurchaseDialog: instyleInfiniteTipser.openPurchaseDialog.bind(instyleInfiniteTipser),
        closeDialog: instyleInfiniteTipser.closeDialog.bind(instyleInfiniteTipser),
      },
    };
  }

  componentDidMount() {
    window.addEventListener('tipser_cart_changed', this.handleTipserCartChanged);
    window.addEventListener('wishlist-overlay', this.hideOnWishlistOpen);
    this.button.addEventListener('click', this.handleTipserIconClick);
  }

  componentDidUpdate() {
    const { isLayerVisible, cartSize } = this.state;
    this.dispatchToggleEvent();
    if (isLayerVisible && cartSize === 0) {
      window.addEventListener('click', this.handleOutsideClick);
      this.overlay.querySelector('.tipser__close-button').addEventListener('click', this.handleTipserIconClick);
    }
    else {
      window.removeEventListener('click', this.handleOutsideClick);
    }
  }

  componentWillUnmount() {
    window.removeEventListener('tipser_cart_changed', this.handleTipserCartChanged);
  }

  toggleLayer() {
    const { isLayerVisible } = this.state;
    this.setState({
      isLayerVisible: !isLayerVisible,
    });
  }

  layerDecision() {
    const { tipserSDK, cartSize } = this.state;
    if (cartSize === 0) {
      this.toggleLayer();
      return;
    }
    tipserSDK.openPurchaseDialog();
  }

  handleTipserIconClick() {
    const { isLayerVisible, cartSize } = this.state;

    window.dataLayer.push({ event: 'clickTipserIcon', isLayerVisible, cartSize });
    this.layerDecision();
  }

  handleOutsideClick(e) {
    if (this.overlay.contains(e.target)) return;
    this.toggleLayer();
  }

  handleTipserCartChanged(e) {
    const { cartSize } = e.detail;
    this.setState({ cartSize });
    window.localStorage.setItem('cartSize', cartSize);
  }

  hideOnWishlistOpen(e) {
    const { isLayerVisible } = this.state;
    if (e.detail.isLayerVisible && isLayerVisible) {
      this.toggleLayer();
    }
  }

  dispatchToggleEvent() {
    const { isLayerVisible } = this.state;
    const event = new CustomEvent('tipser-overlay', { detail: { isLayerVisible } });
    window.dispatchEvent(event);
  }

  render() {
    const { cartSize, isLayerVisible } = this.state;

    return (
      <div
        className="tipser__wrapper"
        ref={(node) => {
          this.overlay = node;
        }}
      >
        <button
          type="button"
          className="tipser__button"
          ref={(node) => {
            this.button = node;
          }}
        >
          { cartSize > 0 && (
            <span className="tipser__cart-size">
              { cartSize }
            </span>
          ) }
        </button>
        { isLayerVisible && (<TipserLayer />) }
      </div>
    );
  }
}

export default TipserIcon;
