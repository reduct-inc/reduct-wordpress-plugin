<?php
function get_reel_url_from_id($id)
{
    $VIDEO_RESOURCE_URL = "https://app.reduct.video/e/";
    return $VIDEO_RESOURCE_URL . $id . "/";
}
function fetch_transcript($id)
{
    $VIDEO_RESOURCE_URL = "https://app.reduct.video/e/";
    $path = $VIDEO_RESOURCE_URL . $id . "/transcript.json";

    $transcript_data = @file_get_contents($path);

    if ($transcript_data == false) {
        return false;
    }

    return $transcript_data;
}
function generate_template($reelId, $transcriptHeight, $borderRadius, $highlightColor, $playable = true)
{
    $uniqueId = uniqid();
    $stringifiedTranscript = fetch_transcript($reelId);

    if ($stringifiedTranscript == false) {
        throw new Exception("Failed to load transcript data.");
    }

    $transcriptJson = json_decode($stringifiedTranscript);
    $segments = $transcriptJson->segments;
    $site_url = get_site_url();
    $manifestUrl = get_reel_url_from_id($reelId) . "burn?type=json";
    $manifest = @file_get_contents($manifestUrl);
    $transcriptUrl = get_reel_url_from_id($reelId);
    $posterUrl = get_reel_url_from_id($reelId) . "posterframe.jpg";

    if ($manifest == false) {
        throw new Exception("Failed to load manifest data.");
    }

    if ($manifest === false) {
        return false;
    }

    ob_start();
    ?>
    <div class='reduct-plugin-container' id="reduct-plugin-video-<?php echo $uniqueId ?>"
        style="borderRadius: <?php echo $borderRadius ?>;">
        <video class='reduct-plugin-video' controls poster="<?php echo $posterUrl ?> playsinline webkit-playsinline"></video>
        <div class='reduct-plugin-transcript-wrapper' style="height: <?php echo $transcriptHeight ?>;">
            <button class='reduct-plugin-expand-btn'>↓</button>
            <div class="reduct-plugin-transcript">
                <?php
                foreach ($segments as $paragraph) {
                    $wdlist = $paragraph->wdlist;
                    $speakerName = "Unnamed Speaker";
                    ?>
                    <div class='reduct-plugin-transcript-speaker'>
                        <?php echo $speakerName ?>
                    </div>

                    <p class='reduct-plugin-transcript-paragraph'>
                        <?php
                        foreach ($wdlist as $wd) {
                            $word = $wd->word;
                            $start = $wd->start;
                            $end = $wd->end;
                            ?>
                            <span class='reduct-plugin-transcript-word' data-start="<?php echo $start ?>"
                                data-end="<?php echo $end ?>">
                                <?php echo $word ?>
                            </span>
                            <?php
                        }
                        ?>
                    </p>
                    <?php
                }
                ?>
                <div class='reduct-plugin-info-tooltip'>
                    💡 Quick tip: Click a word in the transcript below to navigate the video.
                </div>
            </div>
        </div>
        <button class='reduct-plugin-scroll-button'>
            Scroll to Playhead
        </button>
    </div>
    <script>
        (async function () {
            const site_url = `<?php echo $site_url ?>`;
            const id = `<?php echo $uniqueId ?>`;
            const transcriptHeight = `<?php echo $transcriptHeight ?>`;
            const borderRadius = `<?php echo $borderRadius ?>`;
            const transcriptUrl = `<?php echo $transcriptUrl ?>`;
            const stringifiedManifest = `<?php echo $manifest ?>`;
            const highlightColor = `<?php echo $highlightColor ?>`;

            function loadReductApiScript() {
                return new Promise((res, rej) => {
                    const script = document.createElement('script');
                    script.type = 'text/javascript';
                    script.async = true;
                    script.src = 'https://app.reduct.video/api.js';

                    script.addEventListener('load', () => {
                        res('Success.');
                    });

                    script.addEventListener('error', () => {
                        rej({
                            message: 'Error loading script.',
                        });
                    });

                    document.head.appendChild(script);
                });
            }

            async function waitForScriptLoad(checkInterval = 1000) {
                return new Promise((res, _) => {
                    const timer = setInterval(() => {
                        if (window.Reduct.getSharePlayerFromManifest) {
                            clearInterval(timer);
                            res();
                        }
                    }, checkInterval);
                });
            }

            function loadTranscriptEvent() {
                const transcriptHeightValue = parseInt(transcriptHeight);

                const container = document.getElementById(`reduct-plugin-video-${id}`);

                const video = container.querySelector('.reduct-plugin-video');
                const scrollToPayloadButton = container.querySelector(
                    '.reduct-plugin-scroll-button'
                );
                const tooltip = container.querySelector('.reduct-plugin-info-tooltip');
                const expandButton = container.querySelector('.reduct-plugin-expand-btn');
                const transcriptEle = container.querySelector('.reduct-plugin-transcript');
                const transcriptWrapper = container.querySelector(
                    '.reduct-plugin-transcript-wrapper'
                );

                const words = container.querySelectorAll(`.reduct-plugin-transcript-word`);

                transcriptWrapper.style.height = transcriptHeight;
                container.style.borderRadius = borderRadius;

                async function loadVideo() {
                    const manifest = JSON.parse(stringifiedManifest);
                    const siteUrl = site_url;
                    const url = `${siteUrl}/?rest_route=/reduct-plugin/v1/video/${transcriptUrl.split('/e/')[1]
                        }`;

                    Reduct.getSharePlayerFromManifest(video, manifest, url);
                }

                loadVideo();

                if (!transcriptEle) {
                    return;
                }

                transcriptEle.addEventListener('click', (e) => {
                    const element = e.target;
                    if (element.classList.contains(`reduct-plugin-transcript-word`)) {
                        const startTime = element.getAttribute('data-start');
                        if (startTime) {
                            video.currentTime = parseFloat(startTime);
                            scrollToPayloadButton.style.display = 'none';
                            video.ontimeupdate = syncTranscriptVideo;
                        }
                    }
                });

                const getCurrentWord = () => {
                    const currentTime = video.currentTime;
                    for (let word of words) {
                        const endTime = word.getAttribute('data-end');

                        if (currentTime < parseFloat(endTime) - 0.1) return word;
                    }
                };

                function syncTranscriptVideo() {
                    const currentWord = getCurrentWord();

                    for (let word of words) {
                        // setting defaults for visited words
                        word.style.backgroundColor = 'transparent';
                        word.style.borderRadius = '0px';

                        if (word === currentWord) {
                            word.style.backgroundColor = highlightColor;
                            word.style.borderRadius = '5px';
                            word.style.transitionProperty = 'left, top, width, height';
                            word.style.transitionDuration = '0.1s';

                            const wordHeight = word.offsetHeight;

                            const transcriptScrollPos = transcriptEle.offsetTop;
                            const wordScrollPos = word.offsetTop;

                            const wordRelativePos = wordScrollPos - transcriptScrollPos;

                            const visiblePreceedingLines = 3;

                            if (!isInViewport(word, transcriptEle)) {
                                transcriptEle.scroll(
                                    0,
                                    wordRelativePos - visiblePreceedingLines * wordHeight
                                );
                            }
                        }
                    }
                }

                function isInViewport(element, container) {
                    if (!element || !container) return false;

                    const containerRect = container.getBoundingClientRect();
                    const elementRect = element.getBoundingClientRect();

                    if (
                        elementRect.bottom > containerRect.top &&
                        elementRect.bottom < containerRect.bottom
                    ) {
                        return true;
                    }

                    return false;
                }

                video.ontimeupdate = syncTranscriptVideo;
                transcriptEle.addEventListener('scroll', (e) => {
                    const currentWord = getCurrentWord();

                    if (isInViewport(currentWord, transcriptEle)) {
                        video.ontimeupdate = syncTranscriptVideo;
                        scrollToPayloadButton.style.display = 'none';
                        return;
                    }

                    if (!video.paused) {
                        scrollToPayloadButton.style.display = 'block';
                    }
                    video.ontimeupdate = null;
                });

                scrollToPayloadButton.addEventListener('click', function () {
                    video.ontimeupdate = syncTranscriptVideo;
                });

                const togglePlayPause = () => {
                    if (video.paused) {
                        video.play();
                        return;
                    }

                    video.pause();
                };

                const toggleTranscriptExpansion = () => {
                    const wrapper = transcriptWrapper || transcriptEle;

                    const transcriptContainerStyle =
                        getComputedStyle(wrapper)?.style || wrapper.style;

                    const height = transcriptContainerStyle.height;

                    const heightValue = parseInt(height);

                    if (heightValue <= transcriptHeightValue) {
                        wrapper.style.setProperty(
                            'height',
                            `${transcriptHeightValue + 80}px`,
                            'important'
                        );

                        if (expandButton) {
                            expandButton.style.setProperty(
                                'transform',
                                'rotate(180deg)',
                                'important'
                            );
                        }
                        return;
                    }

                    if (video.paused) {
                        wrapper.style.setProperty('height', transcriptHeight);
                        if (expandButton) {
                            expandButton.style.setProperty('transform', 'rotate(0deg)');
                        }
                    }
                };

                const hideTooltipFn = () => {
                    container.removeEventListener('click', hideTooltipFn);
                    setTimeout(() => {
                        tooltip && (tooltip.style.display = 'none');
                    }, 4000);
                };

                video.addEventListener('play', () => {
                    toggleTranscriptExpansion();
                    const currentWord = getCurrentWord();

                    if (!isInViewport(currentWord, transcriptEle)) {
                        scrollToPayloadButton.style.display = 'block';
                    }
                });

                video.addEventListener('pause', () => {
                    // if the pause event is fired from video seek ignore it
                    if (video.readyState !== 4) return;

                    toggleTranscriptExpansion();
                    scrollToPayloadButton.style.display = 'none';
                });

                container.addEventListener('click', hideTooltipFn);
                expandButton && expandButton.addEventListener('click', togglePlayPause);
            }

            if (!window.Reduct) {
                window.Reduct = {};
                await loadReductApiScript();
            }

            if (!window.Reduct.getSharePlayerFromManifest) {
                await waitForScriptLoad();
            }

            loadTranscriptEvent();
        })()
    </script>
    <?php
    $output = ob_get_clean();
    return $output;
}