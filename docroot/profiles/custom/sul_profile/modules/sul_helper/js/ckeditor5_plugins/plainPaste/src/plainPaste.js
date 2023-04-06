import { Plugin } from 'ckeditor5/src/core';
import plainTextToHtml from '@ckeditor/ckeditor5-clipboard/src/utils/plaintexttohtml';

export default class PlainPaste extends Plugin {

  static get requires() {
    return [];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'PlainPaste';
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;

    const editingView = editor.editing.view;

    editingView.document.on('clipboardInput', (evt, data) => {
      const dataTransfer = data.dataTransfer;
      let content = plainTextToHtml(dataTransfer.getData('text/plain'));
      data.content = this.editor.data.htmlProcessor.toView(content);
    });
  }

}
