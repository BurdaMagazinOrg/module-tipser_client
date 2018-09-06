import React from 'react';
import ReactDOM from 'react-dom';
import TipserIcon from './components/TipserIcon';

document.querySelectorAll('.tipser-icon-container').forEach((domContainer) => {
  ReactDOM.render(<TipserIcon />, domContainer);
});
