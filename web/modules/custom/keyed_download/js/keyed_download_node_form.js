(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.keyedDownloadNodeForm = {
    attach: function (context, settings) {
      // Only run on keyed_download node forms
      if (!$('form[id*="node-keyed-download-"]', context).length) {
        return;
      }

      // Handle copy functionality for the URL
      $('.keyed-download-copy-btn', context).once('keyed-download-copy').click(function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var urlText = $btn.siblings('.keyed-download-url').text();
        
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(urlText).then(function() {
            showCopyFeedback($btn);
          }).catch(function() {
            fallbackCopy(urlText, $btn);
          });
        } else {
          fallbackCopy(urlText, $btn);
        }
      });

      // Update URL display when secret key field changes manually
      $('#edit-field-secret-key-0-value', context).once('keyed-download-url-update').on('input', function() {
        var newKey = $(this).val();
        var baseUrl = window.location.protocol + '//' + window.location.host;
        var newUrl = baseUrl + '/special-download/' + newKey;
        $('.keyed-download-url').text(newUrl);
      });
      
      // Add download statistics display
      $('#edit-field-download-count-0-value', context).once('keyed-download-stats').each(function() {
        var $countField = $(this);
        var $limitField = $('#edit-field-download-limit-0-value');
        var $wrapper = $countField.closest('.form-item');
        
        function updateStats() {
          var count = parseInt($countField.val()) || 0;
          var limit = parseInt($limitField.val()) || 1;
          var remaining = Math.max(0, limit - count);
          var percentage = limit > 0 ? (count / limit * 100) : 0;
          
          var $statsDisplay = $wrapper.find('.keyed-download-stats');
          if ($statsDisplay.length === 0) {
            $statsDisplay = $('<div class="keyed-download-stats" style="margin-top: 10px;"></div>');
            $wrapper.append($statsDisplay);
          }
          
          $statsDisplay.html(
            '<div style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">' +
            '<strong>Download Progress:</strong> ' + count + ' / ' + limit + ' (' + remaining + ' remaining)<br>' +
            '<div style="background: #e0e0e0; height: 10px; margin-top: 5px; border-radius: 5px;">' +
            '<div style="background: ' + (percentage >= 100 ? '#d32f2f' : '#4caf50') + '; height: 100%; width: ' + Math.min(100, percentage) + '%; border-radius: 5px;"></div>' +
            '</div></div>'
          );
        }
        
        updateStats();
        $countField.on('input', updateStats);
        $limitField.on('input', updateStats);
      });
      
      function showCopyFeedback($btn) {
        $btn.text('Copied!').addClass('button--primary');
        setTimeout(function() {
          $btn.text('Copy URL').removeClass('button--primary');
        }, 2000);
      }
      
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