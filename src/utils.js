async function fetchTranscript(siteUrl, url) {
  const transcriptRes = await fetch(
    `${siteUrl}/?rest_route=/reduct-plugin/v1/transcript/${url.split('/e/')[1]}`
  );

  const transcript = await transcriptRes.json();
  return transcript;
}

function transcriptToText(json) {
  let text = '';
  const segments = json.segments;

  for (let paragraph of segments) {
    const { wdlist } = paragraph;
    wdlist.forEach((wd) => {
      text += wd.word;
    });
  }

  return text;
}

export { fetchTranscript, transcriptToText };
