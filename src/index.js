// dependency added from the php wp
import { useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import IconImg from './icon.svg';
import generateDomFromTranscript from './reelDOM';
import { fetchTranscript } from './utils';

const Icon = <img src={IconImg} />;

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
    const [previewElement, setPreviewElement] = useState(
      props.attributes.domElement || null
    );

    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);
    
    async function updateUrl() {
      try {
        setErrorMsg('');
        setSaving(true);
        if (!url.startsWith('https://app.reduct.video/e/')) {
          setErrorMsg('Invalid URL. Please Enter a valid share link.');
          return;
        }

        const siteUrl = WP_PROPS.site_url;

        const transcript = await fetchTranscript(siteUrl, url);
        const domElement = generateDomFromTranscript(transcript, uniqueId, url);

        props.setAttributes({ url });
        props.setAttributes({ domElement });
        props.setAttributes({ uniqueId });

        setPreviewElement(domElement);
        openModal();
      } catch (e) {
        setErrorMsg(e.message || 'Error saving.');
      } finally {
        setSaving(false);
      }
    }

    return (
      <div style={{ padding: '20px' }}>
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
        <div
          dangerouslySetInnerHTML={{ __html: previewElement }}
          style={{ marginTop: '20px', float: 'none' }}></div>
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
