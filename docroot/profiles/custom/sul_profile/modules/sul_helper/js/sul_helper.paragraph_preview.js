(($, Drupal ) => {
  Drupal.behaviors.sulHelperParagraphPreview = {
    attach: function attach(context, settings) {
      window.addEventListener('message', ({data}) => {
        const messageData = JSON.parse(data);

        $('#' + messageData.id).attr('height', messageData.height);
      });

      $('iframe.next-site-preview', context).each(function (i, iframe) {
        $(iframe).on('load', () => {
          setTimeout(() => {
            iframe.contentWindow.postMessage($(iframe).attr('id'), '*');
            iframe.contentWindow.postMessage($(iframe).attr('edit-data'), '*');
          }, 500);
        })
      });

    },
  };
})(jQuery, Drupal);
