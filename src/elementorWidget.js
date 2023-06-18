import generateDomFromTranscript from './reelDOM';

async function cacheTranscript(siteUrl, url) {
  const transcriptRes = await fetch(
    `${siteUrl}/?rest_route=/reduct-plugin/v1/transcript/${url.split('/e/')[1]}`
  );

  const transcript = await transcriptRes.json();
  return transcript;
}

(function ($) {
  $(window).on('elementor/frontend/init', function () {
    if (!window.elementor) return;
    elementor.channels.editor.on('embedReductReelsButtonEvent', async (e) => {
      const parentNode = e.$el[0].parentNode;

      const url = parentNode.querySelector('input').value;

      const hiddenDOM = parentNode.querySelector(
        '[data-setting=reductDomElement]'
      );

      const uniqueIdDOM = parentNode.querySelector('[data-setting=uniqueId]');

      if (!uniqueIdDOM.value) {
        uniqueIdDOM.value = Date.now();
      }

      const transcript = await cacheTranscript(WP_PROPS.site_url, url);

      const DOMElement = generateDomFromTranscript(
        transcript,
        uniqueIdDOM.value,
        url
      );

      hiddenDOM.value = DOMElement;
      const inputEvent = new Event('input', { bubbles: true });

      hiddenDOM.dispatchEvent(inputEvent);
      uniqueIdDOM.dispatchEvent(inputEvent);
    });
  });
})(jQuery);
