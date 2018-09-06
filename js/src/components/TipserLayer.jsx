import React from 'react';
import '../../../css/TipserLayer.css';

const TipserLayer = () => (
  <React.Fragment>
    <div className="tipser__flyout-title">
      <div className="tipser__close-button" />
      <div className="tipser__title">
            Warenkorb
      </div>
      <div className="tipser__subheading">
            Keine Artikel im Warenkorb
      </div>
      <a className="tipser__cart-button" href="/shop-it">
            Produkte entdecken
      </a>
    </div>
  </React.Fragment>
);

export default TipserLayer;
