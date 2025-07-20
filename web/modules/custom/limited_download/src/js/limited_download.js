(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.limitedDownload = {
    attach: function (context, settings) {
      // Add copy functionality to the URL display
      $('#secret-key-ajax-wrapper code', context).once('limited-download').each(function() {
        var $code = $(this);
        var $wrapper = $code.parent();
        
        // Remove any existing copy button to prevent duplicates
        $wrapper.find('.copy-url-btn').remove();
        
        // Add copy button
        var $copyBtn = $('<button type="button" class="button button--small copy-url-btn" style="margin-left: 10px;">Copy URL</button>');
        
        $copyBtn.click(function(e) {
          e.preventDefault();
          
          // Use modern clipboard API if available, fallback to old method
          var urlText = $code.text();
          
          if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(urlText).then(function() {
              showCopyFeedback($copyBtn);
            }).catch(function() {
              fallbackCopy(urlText, $copyBtn);
            });
          } else {
            fallbackCopy(urlText, $copyBtn);
          }
        });
        
        $wrapper.append($copyBtn);
      });
      
      // Update URL display when secret key changes manually
      $('#edit-secret-key', context).on('input', function() {
        var newKey = $(this).val();
        var baseUrl = window.location.protocol + '//' + window.location.host;
        var newUrl = baseUrl + '/special-download/' + newKey;
        $('#secret-key-ajax-wrapper code').text(newUrl);
      });
      
      // Function to show copy feedback
      function showCopyFeedback($btn) {
        $btn.text('Copied!').addClass('button--primary');
        setTimeout(function() {
          $btn.text('Copy URL').removeClass('button--primary');
        }, 2000);
      }
      
      // Fallback copy method for older browsers
      function fallbackCopy(text, $btn) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
          document.execCommand('copy');
          showCopyFeedback($btn);
        } catch (err) {
          console.error('Copy failed:', err);
          $btn.text('Copy failed').addClass('button--danger');
          setTimeout(function() {
            $btn.text('Copy URL').removeClass('button--danger');
          }, 2000);
        }
        $temp.remove();
      }
    }
  };

})(jQuery, Drupal);