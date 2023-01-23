<?php

// adding "/" if url is missing it
$base_url = str_ends_with($attributes["url"], "/") ? $attributes["url"] : $attributes["url"] . "/";

$manifest = file_get_contents($base_url . "burn?type=json");
$transcript = file_get_contents($base_url . "transcript.json");

$decoded = json_decode($transcript, true);
$segments = $decoded["segments"];

// creating unique id to make sure each block / html elements has unique set of
// class or id so that there is no collission when rendered multiple components 
// in single page
$id = uniqid("unahc");
?>

<meta name="manifest_<?= htmlspecialchars($id) ?>" content="<?= htmlspecialchars($manifest) ?>">
<meta name="url_<?= htmlspecialchars($id) ?>" content="<?= htmlspecialchars($base_url) ?>">

<div id="container_<?= htmlspecialchars($id) ?>" style="min-width:400px;display:flex;flex-direction:column">
    <video id="reduct-video_<?= htmlspecialchars($id) ?>" controls style="border-radius: 1.25rem 1.25rem 0 0 "></video>
    <div id="transcript_<?= htmlspecialchars($id) ?>"
        style="height: 150px; font-size: 16px;margin-bottom: 0.75rem;overflow-y: scroll;border-radius: 0 0 1.25rem 1.25rem;
                            box-shadow: 0 0.438rem 0.938rem rgb(0 0 0 / 10%); padding: 20px;font-family: sans-serif;">
        <?php
        foreach ($segments as $segment) {
            $segment_start = $segment["start"];
            $segment_end = $segment["end"];
            ?>
            <p class="transcript-paragraph_<?= htmlspecialchars($id) ?>" data-start="<?php echo $segment_start ?>"
                data-end="<?php echo $segment_end ?>" style="border-radius: 5px">
                <?php
                foreach ($segment["wdlist"] as $wordObj) {
                    $word_start = $wordObj["start"];
                    $word_end = $wordObj["end"];
                    $word = $wordObj["word"];
                    ?>
                    <span data-start="<?php echo $word_start ?>" data-end="<?php echo $word_end ?>" style="cursor:pointer"
                        class="transcript-word_<?= htmlspecialchars($id) ?>">
                        <?php echo $word ?>
                    </span>
                <?php
                }
                ?>
            </p>
        <?php
        }
        ?>
    </div>
</div>
<script src="https://app.reduct.video/api.js"></script>
<script>
    // using anonymous function to limit the scope of the variables declared
    (function () {
        const video = document.getElementById("reduct-video_<?= htmlspecialchars($id) ?>");

        async function loadVideo() {
            const manifestFromMeta = document.querySelector('meta[name="manifest_<?= htmlspecialchars($id) ?>"]').content;
            const manifest = JSON.parse(manifestFromMeta);

            const urlFromMeta = document.querySelector('meta[name="url_<?= htmlspecialchars($id) ?>"]').content;
            const url = `${window.origin}/wp-json/reduct-plugin/v1/video/${urlFromMeta.split("/e/")[1]}`

            Reduct.getSharePlayerFromManifest(video, manifest, url)
        }


        // using event delegation
        const transcriptEle = document.getElementById("transcript_<?= htmlspecialchars($id) ?>");

        transcriptEle.addEventListener("click", (e) => {
            const element = e.target
            if (element.classList.contains("transcript-word_<?= htmlspecialchars($id) ?>")) {
                const startTime = element.getAttribute("data-start");
                if (startTime) {
                    video.currentTime = parseFloat(startTime);
                }
            }
        })

        const words = document.querySelectorAll(".transcript-word_<?= htmlspecialchars($id) ?>");

        let lastScrollPosition = 0;

        setInterval(() => {
            const currentTime = video.currentTime;

            for (let word of words) {
                const startTime = word.getAttribute("data-start");
                const endTime = word.getAttribute("data-end")

                if (currentTime >= parseFloat(startTime) && currentTime <= parseFloat(endTime)) {
                    word.style.backgroundColor = "yellow";
                    const transcriptHeight = transcriptEle.offsetHeight;
                    const wordHeight = word.offsetHeight;

                    const transcriptScrollPos = transcriptEle.offsetTop
                    const wordScrollPos = word.offsetTop;

                    continue;
                }

                // setting the highlighted word back to original
                if (word.style.backgroundColor === "yellow") {
                    word.style.backgroundColor = "transparent";
                }
            }
        }, 100)

        loadVideo();
    })()
</script>