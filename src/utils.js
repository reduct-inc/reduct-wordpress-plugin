async function fetchTranscript(siteUrl) {
  const transcriptRes = await fetch(
    `${siteUrl}/?rest_route=/reduct-plugin/v1/transcript/${url.split('/e/')[1]}`
  );

  const transcript = await transcriptRes.json();
  return transcript;
}

export { fetchTranscript };
