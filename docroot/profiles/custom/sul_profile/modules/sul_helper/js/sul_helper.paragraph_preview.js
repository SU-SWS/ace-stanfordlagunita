(($, Drupal ) => {
  Drupal.behaviors.sulHelperParagraphPreview = {
    attach: function attach(context, settings) {
      window.addEventListener('message', ({data}) => {
        const messageData = JSON.parse(data);

        // If the returned data contains a message, do something with it.
        if (messageData.message?.length > 1) {
          switch (messageData.message) {
            case 'refresh':

              $('iframe.next-site-preview').each(function (i, iframe) {
                iframe.contentWindow.postMessage($(iframe).attr('id'), '*');
                iframe.contentWindow.postMessage($(iframe).attr('edit-data'), '*');
              });

              break;
          }
          return;
        }

        $('#' + messageData.id).attr('height', messageData.height);
      });

      // Post a message to the iframe containing all the data from the attributes.
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
