<?php
echo $domElement
    ?>

<script>
    (async function () {
        const id = "<?php echo $id ?>";
        const site_url = "<?php echo $site_url ?>";
        const stringifiedManifest = `<?php echo $manifest ?>`;
        const transcriptHeight = `<?php echo $transcriptHeight ?>`;
        const highlightColor = `<?php echo $highlightColor ?>`;
        const transcriptUrl = `<?php echo $base_url ?>`;
        const borderRadius = `<?php echo strval($borderRadius) ?>`;

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

            let container = document.getElementById(`reduct-plugin-video-${id}`),
                video,
                scrollToPayloadButton,
                tooltip,
                expandButton,
                transcriptEle,
                transcriptWrapper,
                words;

            if (container) {
                video = container.querySelector('.reduct-plugin-video');
                scrollToPayloadButton = container.querySelector(
                    '.reduct-plugin-scroll-button'
                );
                tooltip = container.querySelector('.reduct-plugin-info-tooltip');
                expandButton = container.querySelector('.reduct-plugin-expand-btn');
                transcriptEle = container.querySelector('.reduct-plugin-transcript');
                transcriptWrapper = container.querySelector(
                    '.reduct-plugin-transcript-wrapper'
                );

                words = container.querySelectorAll(`.reduct-plugin-transcript-word`);

                transcriptWrapper.style.height = parseInt(transcriptHeight) + "px";
                container.style.borderRadius = parseInt(borderRadius) + "px";
            } else {
                video = document.getElementById(`reduct-video_${id}`);
                scrollToPayloadButton = document.getElementById(
                    `reduct-video-scroll-button_${id}`
                );
                container = document.getElementById(`reduct-video-container_${id}`);
                tooltip = document.getElementById(`reduct-video-info-tooltip_${id}`);
                transcriptEle = document.getElementById(`transcript_${id}`);
                words = document.querySelectorAll(`.transcript-word_${id}`);

                // older versions dont have height value so take default transcript height
                // also attach transition animation to height change
                transcriptEle.style.height = transcriptHeight;
                transcriptEle.style.setProperty('transition', 'height 0.5s linear');
            }

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

                const height =
                    getComputedStyle(wrapper)?.height || wrapper.style?.height;

                const heightValue = parseInt(height);

                console.log({ heightValue, transcriptHeight })

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
    })();
</script>