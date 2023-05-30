<?php

// adding "/" if url is missing it
$base_url = $attributes["url"];
$domElement = $attributes["domElement"];
$site_url = get_site_url();

if (!str_ends_with($attributes["url"], "/")) {
    $base_url = $attributes["url"] . "/";
}

$manifest = file_get_contents($base_url . "burn?type=json");

$segments = array();

$id = $attributes["uniqueId"];
?>

<meta name="manifest_<?= htmlspecialchars($id) ?>" content="<?= htmlspecialchars($manifest) ?>">
<meta name="url_<?= htmlspecialchars($id) ?>" content="<?= htmlspecialchars($base_url) ?>">

<?php
echo $domElement
    ?>

</div>
<script src="https://app.reduct.video/api.js"></script>
<script>
    // using anonymous function to limit the scope of the variables declared
    (function () {
        const video = document.getElementById("reduct-video_<?= htmlspecialchars($id) ?>");
        const scrollToPayloadButton = document.getElementById("reduct-video-scroll-button_<?= htmlspecialchars($id) ?>")
        const container = document.getElementById("reduct-video-container_<?= htmlspecialchars($id) ?>");
        const tooltip = document.getElementById("reduct-video-info-tooltip_<?= htmlspecialchars($id) ?>");

        async function loadVideo() {
            const manifestFromMeta = document.querySelector('meta[name="manifest_<?= htmlspecialchars($id) ?>"]').content;
            const manifest = JSON.parse(manifestFromMeta);

            const urlFromMeta = document.querySelector('meta[name="url_<?= htmlspecialchars($id) ?>"]').content;
            const siteUrl = "<?= htmlspecialchars($site_url) ?>";
            const url = `${siteUrl}/?rest_route=/reduct-plugin/v1/video/${urlFromMeta.split("/e/")[1]}`;
            Reduct.getSharePlayerFromManifest(video, manifest, url)
        }

        loadVideo();

        // using event delegation
        const transcriptEle = document.getElementById("transcript_<?= htmlspecialchars($id) ?>");

        if (!transcriptEle) {
            return;
        }

        const words = document.querySelectorAll(".transcript-word_<?= htmlspecialchars($id) ?>");

        transcriptEle.addEventListener("click", (e) => {
            const element = e.target
            if (element.classList.contains("transcript-word_<?= htmlspecialchars($id) ?>")) {
                const startTime = element.getAttribute("data-start");
                if (startTime) {
                    video.currentTime = parseFloat(startTime);
                    scrollToPayloadButton.style.display = "none"
                    syncTranscriptVideo();
                }
            }
        })

        let lastWordRelativePosition = 0;
        let lastSelectedWord = null;

        const getCurrentWord = () => {
            const currentTime = video.currentTime;
            for (let word of words) {
                const startTime = word.getAttribute("data-start");
                const endTime = word.getAttribute("data-end");

                if (currentTime < parseFloat(endTime) - 0.1) return word;
            }
        }

        function syncTranscriptVideo() {
            const currentWord = getCurrentWord();

            for (let word of words) {
                // setting defaults for visited words
                word.style.backgroundColor = "transparent";
                word.style.borderRadius = '0px';

                if (word === currentWord) {
                    word.style.backgroundColor = "#FCA59C";
                    word.style.borderRadius = "5px";
                    word.style.transitionProperty = "left, top, width, height";
                    word.style.transitionDuration = "0.1s";

                    const transcriptHeight = transcriptEle.offsetHeight;
                    const wordHeight = word.offsetHeight;

                    const transcriptScrollPos = transcriptEle.offsetTop;
                    const wordScrollPos = word.offsetTop;

                    const wordRelativePos = wordScrollPos - transcriptScrollPos;

                    const visiblePreceedingLines = 3;

                    if (!isInViewport(word, transcriptEle)) {
                        transcriptEle.scroll(0, wordRelativePos - visiblePreceedingLines * wordHeight);
                    }
                }
            }
        }

        function isInViewport(element, container) {
            if (!element || !container) return false;

            const containerRect = container.getBoundingClientRect();
            const elementRect = element.getBoundingClientRect();

            if (elementRect.bottom > containerRect.top && elementRect.bottom < containerRect.bottom) {
                return true;
            }

            return false;
        }

        video.ontimeupdate = syncTranscriptVideo;
        transcriptEle.addEventListener("scroll", (e) => {
            const currentTime = video.currentTime;

            const currentWord = getCurrentWord();

            if (isInViewport(currentWord, transcriptEle)) {
                video.ontimeupdate = syncTranscriptVideo;
                scrollToPayloadButton.style.display = "none"
                return;
            }

            scrollToPayloadButton.style.display = "block"
            video.ontimeupdate = null;
        })

        scrollToPayloadButton.addEventListener("click", function () {
            syncTranscriptVideo();
            video.ontimeupdate = syncTranscriptVideo;
        })

        const hideTooltipFn = () => {
            container.removeEventListener("click", hideTooltipFn)
            setTimeout(() => {
                tooltip && (tooltip.style.display = "none")
            }, 4000)
        }

        container.addEventListener("click", hideTooltipFn)
    })()
</script>