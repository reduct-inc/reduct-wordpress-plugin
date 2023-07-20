const generateDomFromTranscript = ({
  transcript,
  uniqueId,
  url,
  transcriptHeight = "160px",
  borderRadius = "22px",
}) => {
  const transcriptJson = JSON.parse(transcript);
  const container = document.createElement('div');
  const sharedUrl = url.endsWith('/') ? url : url + '/';

  const posterUrl = sharedUrl + 'posterframe.jpg';

  const reactNodes = (
    <>
      <div
        className='reduct-plugin-container'
        id={`reduct-plugin-video-${uniqueId}`}
        style={{ borderRadius }}>
        <video className='reduct-plugin-video' controls poster={posterUrl} />
        <div
          className='reduct-plugin-transcript-wrapper'
          style={{ height: transcriptHeight }}>
          <button className='reduct-plugin-expand-btn'>↓</button>
          <div className='reduct-plugin-transcript'>
            {transcriptJson.segments.map((segment, idx) => {
              const { wdlist, speaker_name = 'Unnamed Speaker' } = segment;
              return (
                <React.Fragment key={idx}>
                  <div className='reduct-plugin-transcript-speaker'>
                    {speaker_name}
                  </div>
                  <p className='reduct-plugin-transcript-paragraph'>
                    {wdlist.map((v, index) => {
                      const { start, end, word } = v;
                      return (
                        <span
                          className='reduct-plugin-transcript-word'
                          data-start={start}
                          data-end={end}
                          key={index}>
                          {word}
                        </span>
                      );
                    })}
                  </p>
                </React.Fragment>
              );
            })}
          </div>
          <div className='reduct-plugin-info-tooltip'>
            💡 Quick tip: Click a word in the transcript below to navigate the
            video.
          </div>
        </div>
        <button className='reduct-plugin-scroll-button'>
          Scroll to Playhead
        </button>
      </div>
    </>
  );

  ReactDOM.render(reactNodes, container);
  return container.innerHTML;
};

export default generateDomFromTranscript;
