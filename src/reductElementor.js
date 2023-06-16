'use strict';

class ReductWidgetHandlerClass extends elementorModules.frontend.handlers.Base {
  constructor() {
    super();

    this.state = {
      url: '',
      uniqueId: Math.random().toString(36).substring(2),
      errorMsg: '',
      isOpen: false,
      saving: false,
      previewElement: null,
    };
  }
  generateDomFromTranscript(transcript, uniqueId, url) {
    const container = document.createElement('div');
    const sharedUrl = url.endsWith('/') ? url : url + '/';

    const posterUrl = sharedUrl + 'posterframe.jpg';

    const css = `
          #reduct-video-container_${uniqueId} {
              min-width: 320px;
              display: flex;
              flex-direction: column;
              position: relative;
              width: 100%;
          }
      
          #reduct-video_${uniqueId} {
              border-radius: 1rem 1rem 0 0;
              width: 100%;
          }
      
          #transcript_${uniqueId} {
              background-color: white;
              height: 160px;
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
          }
          
          #reduct-video-scroll-button_${uniqueId} {
              font-size: 12px;
              position: absolute;
              z-index: 10;
              bottom: 24px;
              left: calc(50% - 80px);
              display: none;
              background: #353535;
              color: white;
              border: none;
              padding: 8px 12px;
            }
            
            #reduct-video-scroll-button_${uniqueId}:hover {
              border: none;
              transform: scale(1.02);
              box-shadow: 4px 4px 4px rgba(53, 53, 53, 0.22);
          }
          
          #reduct-video-info-tooltip_${uniqueId} {
            top: -16px;
            padding: 4px 0px;
            background: #353535;
            font-style: italic;
            color: white;
            font-size: 16px;
            animation: disappearAnimation 5s forwards;
            position: absolute;
            width: 100%;
            text-align: center;
          }
        }
          `;

    const reactNodes = (
      <>
        <style>{css}</style>
        <div id={`reduct-video-container_${uniqueId}`}>
          <video id={`reduct-video_${uniqueId}`} controls poster={posterUrl} />
          <div style={{ position: 'relative' }}>
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
            <div id={`reduct-video-info-tooltip_${uniqueId}`}>
              💡 Quick tip: Click a word in the transcript below to navigate the
              video.
            </div>
            <button id={`reduct-video-scroll-button_${uniqueId}`}>
              Scroll to Playhead
            </button>
          </div>
        </div>
      </>
    );

    ReactDOM.render(reactNodes, container);
    return container.innerHTML;
  }

  openModal = () => {
    this.setState({ isOpen: true });
  };

  closeModal = () => {
    this.setState({ isOpen: false });
  };

  cacheTranscript = async () => {
    const siteUrl = WP_PROPS.site_url;

    const transcriptRes = await fetch(
      `${siteUrl}/?rest_route=/reduct-plugin/v1/transcript/${
        this.state.url.split('/e/')[1]
      }`
    );

    const transcript = await transcriptRes.json();
    return transcript;
  };

  updateUrl = async () => {
    try {
      this.setState({ errorMsg: '', saving: true });

      const { url, uniqueId } = this.state;

      if (!url.startsWith('https://app.reduct.video/e/')) {
        this.setState({
          errorMsg: 'Invalid URL. Please Enter a valid share link.',
        });
        return;
      }

      const transcript = await this.cacheTranscript();
      const domElement = this.generateDomFromTranscript(
        JSON.parse(transcript),
        uniqueId,
        url
      );

      this.props.setAttributes({ url });
      this.props.setAttributes({ domElement });
      this.props.setAttributes({ uniqueId });

      this.setState({ previewElement: domElement });
      this.openModal();
    } catch (e) {
      this.setState({ errorMsg: e.message || 'Error saving.' });
    } finally {
      this.setState({ saving: false });
    }
  };

  widget =
    elementorFrontend.documentsManager.documents[0].elements.models.filter(
      function (model) {
        return model.attributes.widgetType === 'reduct-embed-elementor';
      }
    )[0];
  // Set the data in the widget settings
  attributes = {
    url: this.url,
    domElement: this.domElement,
    uniqueId: this.uniqueId,
  };

  if(widget) {
    console.log({ widget });

    widget.setSettings(attributes);
    widget.render();
  }
}
