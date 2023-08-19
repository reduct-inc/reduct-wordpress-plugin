// dependency added from the php wp
import { useState, useEffect, useRef } from '@wordpress/element';
import { Modal, RangeControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import IconImg from './icon.svg';
import { fetchTranscript, transcriptToText } from './utils';

const Icon = <img src={IconImg} />;

wp.blocks.registerBlockType('reduct-plugin/configs', {
  title: 'Reduct Video Plugin',
  icon: Icon,
  category: 'common',
  attributes: {
    reelId: { type: 'string', default: '' },
    url: { type: 'string', default: '' },
    domElement: { type: 'string' },
    uniqueId: {
      type: 'string',
      default: '',
    },
    transcriptHeight: { type: 'string', default: '160px' },
    borderRadius: { type: 'string', default: '22px' },
    highlightColor: { type: 'string', default: '#FCA59C' },
    transcript: { type: 'string', default: '' },
  },

  edit: function ({
    attributes: { url, uniqueId, transcriptHeight, borderRadius, reelId },
    setAttributes,
  }) {
    const [isOpen, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');
    const [previewElement, setPreviewElement] = useState('');
    const domRef = useRef();

    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    const siteUrl = WP_PROPS.site_url;

    async function fetchDOMElement() {
      if (!reelId) return;
      const element = await fetch(
        `${siteUrl}/wp-json/reduct-plugin/v1/element?id=${reelId}`
      );

      const domElement = await element.text();

      setPreviewElement(domElement);
    }

    async function updateUrl() {
      try {
        setErrorMsg('');
        setSaving(true);
        if (!url.startsWith('https://app.reduct.video/e/')) {
          setErrorMsg('Invalid URL. Please Enter a valid share link.');
          return;
        }

        const transcriptStr = await fetchTranscript(siteUrl, url);
        const transcriptJson = JSON.parse(transcriptStr);
        const transcriptText = transcriptToText(transcriptJson);
        const reelId = url.split('/')[4];

        setAttributes({
          transcript: transcriptText,
          reelId,
          domElement: '',
          uniqueId: '',
        });
        await fetchDOMElement();
        openModal();
      } catch (e) {
        setErrorMsg(e.message || 'Error saving.');
      } finally {
        setSaving(false);
      }
    }

    useEffect(() => {
      fetchDOMElement();
    }, []);

    useEffect(() => {
      if (!domRef.current || !previewElement) return;

      const wrapper = domRef.current.querySelector('.reduct-plugin-container');

      wrapper.style.borderRadius = borderRadius;

      const transcriptWrapper = domRef.current.querySelector(
        '.reduct-plugin-transcript-wrapper'
      );
      transcriptWrapper.style.height = transcriptHeight;
    }, [transcriptHeight, borderRadius, previewElement]);

    return (
      <div {...useBlockProps()} style={{ padding: '20px' }}>
        <h5>Embed Reduct Video</h5>
        <p>Paste the shared URL</p>
        <div style={{ display: 'flex', width: '100%' }}>
          <input
            type='text'
            placeholder='Enter URL to embed...'
            onChange={(e) => setAttributes({ url: e.target.value })}
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
        {/* uniqueId is only present in older versions */}
        {uniqueId && (
          <div style={{ color: 'red' }}>
            To access new features, embed and refresh the page.
          </div>
        )}
        <div
          dangerouslySetInnerHTML={{ __html: previewElement }}
          ref={domRef}
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

        {!uniqueId && (
          <InspectorControls key='setting'>
            <div
              id='gutenpride-controls'
              style={{ fontSize: '16px', padding: '0px 16px' }}>
              <fieldset className='reduct-plugin-input-field'>
                <RangeControl
                  label='Transcript Height (px):'
                  value={parseInt(transcriptHeight)}
                  onChange={(value) =>
                    setAttributes({
                      transcriptHeight: `${value}px`,
                    })
                  }
                  min={160}
                  max={400}
                />
              </fieldset>
              <fieldset className='reduct-plugin-input-field'>
                <RangeControl
                  label='Border Radius (px):'
                  value={parseInt(borderRadius)}
                  onChange={(value) =>
                    setAttributes({
                      borderRadius: `${value}px`,
                    })
                  }
                  min={0}
                  max={40}
                />
              </fieldset>
            </div>
          </InspectorControls>
        )}
      </div>
    );
  },

  save: function () {
    return null;
  },
});
