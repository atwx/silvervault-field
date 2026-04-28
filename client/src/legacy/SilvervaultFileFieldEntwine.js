/* global window */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { loadComponent } from 'lib/Injector';

window.jQuery.entwine('ss', ($) => {
  $('.js-silvervault-mount').entwine({
    Root: null,

    onmatch() {
      let config = {};
      try {
        config = JSON.parse(this.attr('data-config') || '{}');
      } catch (e) {
        config = {};
      }
      const value = this.attr('data-value') || '';
      const name = this.attr('data-name') || '';

      const ReactField = loadComponent('SilvervaultFileField');
      if (!ReactField) {
        return;
      }

      const Root = createRoot(this[0]);
      this.setRoot(Root);
      this._super();

      Root.render(
        <ReactField
          title=""
          hiddenInputName={name}
          value={value}
          data={config}
        />
      );
    },

    onunmatch() {
      const Root = this.getRoot();
      if (Root) {
        Root.unmount();
        this.setRoot(null);
      }
    },
  });
});
