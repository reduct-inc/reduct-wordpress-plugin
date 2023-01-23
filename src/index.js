// dependency added from the php wp
import { useState } from "@wordpress/element";
import { Modal } from "@wordpress/components";

wp.blocks.registerBlockType("reduct-plugin/configs", {
  title: "Reduct Video Plugin",
  icon: "smiley",
  category: "common",
  attributes: {
    url: { type: "string" },
  },

  // what is seen in admin post editor screen
  edit: function (props) {
    const [url, setUrl] = useState(props.attributes.url || "");
    const [isOpen, setOpen] = useState(false);
    const openModal = () => setOpen(true);
    const closeModal = () => setOpen(false);

    function updateUrl() {
      if (!url.startsWith("https://app.reduct.video/e/")) {
        openModal();
        return;
      }
      props.setAttributes({ url });
    }

    return (
      <div style={{ paddingTop: "20px", paddingBottom: "10px" }}>
        <h5>Embed Reduct Video</h5>
        <p>Paste the shared URL</p>
        <div style={{ display: "flex", width: "100%" }}>
          <input
            type="text"
            placeholder="Enter URL to embed..."
            onChange={(e) => setUrl(e.target.value)}
            value={url}
            style={{ flex: 1, padding: "5px 10px" }}
          />
          <button
            style={{
              backgroundColor: "rgb(236, 83, 65)",
              padding: "5px 10px",
              color: "white",
              fontSize: "14px",
              borderRadius: "3px",
              outline: "none",
              border: "none",
              marginLeft: "5px",
            }}
            onClick={updateUrl}
          >
            Embed
          </button>
        </div>
        {isOpen && (
          <Modal title="Invalid URL" onRequestClose={closeModal}>
            <p>Please use a valid url.</p>
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
