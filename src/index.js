// dependency added from the php wp
import { useState, useRef } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import IconImg from './icon.svg';

const Icon = <img src={IconImg} />;

wp.blocks.registerBlockType('reduct-plugin/configs', {
  title: 'Reduct Video Plugin',
  icon: Icon,
  category: 'common',
  attributes: {
    url: { type: 'string' },
    transcript: { type: 'string' },
  },

  // what is seen in admin post editor screen
  edit: function (props) {
    const [url, setUrl] = useState(props.attributes.url || '');
    const [errorMsg, setErrorMsg] = useState('');
    const [isOpen, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);

    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    async function cacheTranscript() {
      const transcriptRes = await fetch(
        `${window.origin}/?rest_route=/reduct-plugin/v1/transcript/${
          url.split('/e/')[1]
        }`
      );

      const transcript = await transcriptRes.json();
      props.setAttributes({ transcript });
    }

    async function updateUrl() {
      try {
        setErrorMsg('');
        setSaving(true);
        if (!url.startsWith('https://app.reduct.video/e/')) {
          setErrorMsg('Invalid URL. Please Enter a valid share link.');
          return;
        }

        await cacheTranscript();

        openModal();
        props.setAttributes({ url });
      } catch (e) {
        setErrorMsg(e.message || 'Error saving.');
      } finally {
        setSaving(false);
      }
    }

    function clearError() {
      errorMessage.current = '';
    }

    return (
      <div style={{ padding: '20px', paddingBottom: '10px' }}>
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
      </div>
    );
  },
  // what public will see with content
  save: function () {
    return null;
  },
});
