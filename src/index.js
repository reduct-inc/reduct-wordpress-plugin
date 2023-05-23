// dependency added from the php wp
import { useState, useEffect } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import IconImg from './icon.svg';

const Icon = <img src={IconImg} />;

const generateDomFromTranscript = (transcript, uniqueId, url) => {
  const container = document.createElement('div');
  const sharedUrl = url.endsWith("/") ? url : url + "/";

  const posterUrl = sharedUrl + "posterframe.jpg";

  const css = `
    #container_${uniqueId} {
        min-width: 320px;
        display: flex;
        flex-direction: column;
    }

    #reduct-video_${uniqueId} {
        border-radius: 1rem 1rem 0 0;
        width: 100%;
    }

    #transcript_${uniqueId} {
        background-color: white;
        height: 150px;
        font-size: 16px;
        margin-bottom: 0.75rem;
        overflow-y: scroll;
        border-radius: 0 0 1rem 1rem;
        box-shadow: 0 0.438rem 0.938rem rgb(0 0 0 / 10%);
        padding: 20px;
        font-family: sans-serif;
        scroll-behavior: smooth;
    }

    .speaker_${uniqueId} {
        font-size: 12px;
        color: #B3B3B3;
        margin-bottom: 3px
    }

    .transcript-word_${uniqueId} {
        cursor: pointer;
        padding: 2px;
    }

    .transcript-paragraph_${uniqueId} {
        margin-bottom: 10px;
    }`;

  const reactNodes = (
    <>
      <style>{css}</style>
      <div id={`container_${uniqueId}`}>
        <video id={`reduct-video_${uniqueId}`} controls poster={posterUrl}/>
        <div id={`transcript_${uniqueId}`}>
          {transcript.segments.map((segment, idx) => {
            const { wdlist, speaker_name = 'Unnamed Speaker' } = segment;
            return (
              <React.Fragment key={idx}>
                <div className={`speaker_${uniqueId}`}>{speaker_name}</div>
                <p className={`transcript-paragraph_${uniqueId}`}>
                  {wdlist.map((v, index) => {
                    const { start, end, word } = v;
                    return (
                      <span
                        className={`transcript-word_${uniqueId}`}
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
      </div>
    </>
  );

  ReactDOM.render(reactNodes, container);
  return container.innerHTML;
};

wp.blocks.registerBlockType('reduct-plugin/configs', {
  title: 'Reduct Video Plugin',
  icon: Icon,
  category: 'common',
  attributes: {
    url: { type: 'string' },
    domElement: { type: 'string' },
    uniqueId: { type: 'string' },
  },

  // what is seen in admin post editor screen
  edit: function (props) {
    const [url, setUrl] = useState(props.attributes.url || '');
    const [uniqueId, _] = useState(
      props.attributes.uniqueId || Math.random().toString(36).substring(2)
    );
    const [errorMsg, setErrorMsg] = useState('');
    const [isOpen, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
      (async function () {
        const fromDB = await props.attributes.domElement;
        console.log({ fromDB });
      })();
    }, []);

    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    async function cacheTranscript() {
      // accessing site url property of wp
      const siteUrl = WP_PROPS.site_url;

      const transcriptRes = await fetch(
        `${siteUrl}/?rest_route=/reduct-plugin/v1/transcript/${
          url.split('/e/')[1]
        }`
      );

      const transcript = await transcriptRes.json();
      return transcript;
    }

    async function updateUrl() {
      try {
        setErrorMsg('');
        setSaving(true);
        if (!url.startsWith('https://app.reduct.video/e/')) {
          setErrorMsg('Invalid URL. Please Enter a valid share link.');
          return;
        }

        const transcript = await cacheTranscript();
        const domElement = generateDomFromTranscript(
          JSON.parse(transcript),
          uniqueId,
          url
        );

        props.setAttributes({ url });
        props.setAttributes({ domElement });
        props.setAttributes({ uniqueId });

        document.querySelector('.preview').innerHTML = domElement;
        openModal();
      } catch (e) {
        setErrorMsg(e.message || 'Error saving.');
      } finally {
        setSaving(false);
      }
    }

    return (
      <div style={{ padding: '20px', paddingBottom: '600px' }}>
        <h5>Embed Reduct Video</h5>
        <p>Paste the shared URL</p>
        <div style={{ display: 'flex', width: '100%' }}>
          <input
            type='text'
            placeholder='Enter URL to embed...'
            onChange={(e) => setUrl(e.target.value)}
            value={url}
            style={{ flex: 1, padding: '5px 10px' }}
          />
          <button
            style={{
              backgroundColor: 'rgb(236, 83, 65)',
              padding: '5px 10px',
              color: 'white',
              fontSize: '14px',
              borderRadius: '3px',
              outline: 'none',
              border: 'none',
              marginLeft: '5px',
              opacity: saving ? 0.4 : 1,
            }}
            disabled={saving}
            onClick={updateUrl}>
            {saving ? 'Saving' : 'Embed'}
          </button>
        </div>
        {errorMsg ? (
          <div style={{ fontSize: '16px', color: 'rgb(236, 83, 65)' }}>
            {errorMsg}
          </div>
        ) : (
          ''
        )}
        {isOpen && (
          <Modal title={'Success'} onRequestClose={closeModal}>
            <p>{'Saved'}</p>
          </Modal>
        )}
        <div className='preview' style={{ marginTop: '20px' }}></div>
      </div>
    );
  },
  // what public will see with content
  save: function () {
    return null;
  },
});
