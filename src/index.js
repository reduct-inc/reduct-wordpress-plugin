// dependency added from the php wp
wp.blocks.registerBlockType("reduct-plugin/configs", {
  title: "Reduct Video Plugin",
  icon: "smiley",
  category: "common",
  attributes: {
    url: { type: "string" },
    id: {type: "string"}
  },
  // what is seen in admin post editor screen
  edit: function (props) {
    function updateUrl(e) {
      props.setAttributes({ url: e.target.value });

      if(!props.attributes.id) {
        props.setAttributes({id: Math.random().toString(16).slice(2)})
      }
    }

    return (
      <div>
        <input
          type="text"
          placeholder="Put the url here"
          onChange={updateUrl}
          value={props.attributes.url}
        />
      </div>
    );
  },
  // what public will see with content
  save: function () {
    return null;
  },
});
