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
    transcript: {type: 'string'}
  },

  // what is seen in admin post editor screen
  edit: function (props) {
    const [url, setUrl] = useState(props.attributes.url || '');
    const [isOpen, setOpen] = useState(false);

    const isSuccess = useRef(true);

    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    async function updateUrl() {
      if (!url.startsWith('https://app.reduct.video/e/')) {
        isSuccess.current = false;
        openModal();
        return;
      }

      const transcriptRes = await fetch(
        `${window.origin}/?rest_route=/reduct-plugin/v1/transcript/${
          url.split('/e/')[1]
        }`
      );

      const transcript = await transcriptRes.json();
      
      isSuccess.current = true;
      openModal();
      props.setAttributes({ url });
      props.setAttributes({ transcript });
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
            }}
            onClick={updateUrl}>
            Embed
          </button>
        </div>
        {isOpen && (
          <Modal
            title={isSuccess.current ? 'Success' : 'Invalid URL'}
            onRequestClose={closeModal}>
            <p>{isSuccess.current ? 'Saved' : 'Please use a valid url.'}</p>
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
