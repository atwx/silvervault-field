/* global window */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { loadComponent } from 'lib/Injector';

window.jQuery.entwine('ss', ($) => {
  $('.js-injector-boot .silvervault-file-field').entwine({
    Root: null,
    Component: null,

    onmatch() {
      const cmsContent = this.closest('.cms-content').attr('id');
      const context = cmsContent ? { context: cmsContent } : {};
      const schema = this.data('schema');
      if (schema) {
        this.data('entwine-value', schema.value);
        const Root = createRoot(this[0]);
        const ReactField = loadComponent(schema.component, context);
        this.setRoot(Root);
        this.setComponent(ReactField);
        this._super();
        this.refresh();
      }
    },

    onunmatch() {
      const Root = this.getRoot();
      if (Root) {
        Root.unmount();
      }
    },

    getProps() {
      return {
        ...this.data('schema'),
        value: this.data('entwine-value') || '',
        onChange: this.handleChange.bind(this),
      };
    },

    refresh() {
      const Root = this.getRoot();
      const ReactField = this.getComponent();
      const props = this.getProps();
      Root.render(<ReactField {...props} noHolder />);
    },

    handleChange(value) {
      this.data('entwine-value', value);
      this.refresh();
    },
  });

  $('.cms-edit-form').entwine({
    getChangeTrackerOptions() {
      const isDefault = (this.entwineData('ChangeTrackerOptions') === undefined);
      let opts = this._super();
      if (isDefault) {
        opts = $.extend({}, opts);
        opts.ignoreFieldSelector += ', .silvervault-file-field .no-change-track :input';
        this.setChangeTrackerOptions(opts);
      }
      return opts;
    }
  });
});
