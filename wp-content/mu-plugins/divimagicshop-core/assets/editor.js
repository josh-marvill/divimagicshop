/* Editor side of the Divimagicshop blocks: a live server-rendered preview plus a few settings.
   Plain JS on the wp.* globals, so there is no build step. Block metadata comes from PHP. */
(function (wp) {
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;
	var useSelect = wp.data.useSelect;

	function preview(name, props, extraArgs) {
		return el(ServerSideRender, {
			block: name,
			attributes: props.attributes,
			urlQueryArgs: extraArgs || {},
		});
	}

	function categoryOptions() {
		var terms = useSelect(function (select) {
			return select('core').getEntityRecords('taxonomy', 'product_cat', { per_page: 100, hide_empty: false });
		}, []);
		var options = [{ label: '— Choose a category —', value: '' }];
		(terms || []).forEach(function (t) {
			options.push({ label: t.name, value: t.slug });
		});
		return options;
	}

	registerBlockType('divimagicshop/shelves', {
		edit: function (props) {
			var a = props.attributes;
			return el('div', useBlockProps(),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Shelves' },
						el(RangeControl, {
							label: 'Products per shelf', min: 2, max: 16, value: a.perShelf,
							onChange: function (v) { props.setAttributes({ perShelf: v }); },
						}),
						el(TextControl, {
							label: 'Hide categories (slugs, comma-separated)', value: a.exclude,
							onChange: function (v) { props.setAttributes({ exclude: v }); },
						})
					)
				),
				preview('divimagicshop/shelves', props)
			);
		},
		save: function () { return null; },
	});

	registerBlockType('divimagicshop/shelf', {
		edit: function (props) {
			var a = props.attributes;
			return el('div', useBlockProps(),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Shelf' },
						el(SelectControl, {
							label: 'Category', value: a.category, options: categoryOptions(),
							onChange: function (v) { props.setAttributes({ category: v }); },
						}),
						el(TextControl, {
							label: 'Heading (leave empty for the category name)', value: a.title,
							onChange: function (v) { props.setAttributes({ title: v }); },
						}),
						el(RangeControl, {
							label: 'Products', min: 2, max: 16, value: a.perShelf,
							onChange: function (v) { props.setAttributes({ perShelf: v }); },
						})
					)
				),
				preview('divimagicshop/shelf', props)
			);
		},
		save: function () { return null; },
	});

	registerBlockType('divimagicshop/product-grid', {
		edit: function (props) {
			var a = props.attributes;
			return el('div', useBlockProps(),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Products' },
						el(SelectControl, {
							label: 'Show', value: a.source,
							options: [
								{ label: 'Newest', value: 'new' },
								{ label: 'On sale', value: 'sale' },
								{ label: 'Featured (starred in Products)', value: 'featured' },
							],
							onChange: function (v) { props.setAttributes({ source: v }); },
						}),
						el(TextControl, {
							label: 'Heading', value: a.title,
							onChange: function (v) { props.setAttributes({ title: v }); },
						}),
						el(RangeControl, {
							label: 'Products', min: 2, max: 24, value: a.count,
							onChange: function (v) { props.setAttributes({ count: v }); },
						})
					)
				),
				preview('divimagicshop/product-grid', props)
			);
		},
		save: function () { return null; },
	});

	registerBlockType('divimagicshop/category-tiles', {
		edit: function (props) {
			return el('div', useBlockProps(),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Tiles' },
						el(RangeControl, {
							label: 'Categories (first ones in WooCommerce\'s category order)', min: 2, max: 24, value: props.attributes.count,
							onChange: function (v) { props.setAttributes({ count: v }); },
						})
					)
				),
				preview('divimagicshop/category-tiles', props)
			);
		},
		save: function () { return null; },
	});

	registerBlockType('divimagicshop/product-search', {
		edit: function (props) {
			return el('div', useBlockProps(),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Search' },
						el(TextControl, {
							label: 'Placeholder', value: props.attributes.placeholder,
							onChange: function (v) { props.setAttributes({ placeholder: v }); },
						})
					)
				),
				preview('divimagicshop/product-search', props)
			);
		},
		save: function () { return null; },
	});

	// Blocks that show something about the post they sit in (a tutorial, a product),
	// or about the shop as a whole: a preview and no settings.
	['divimagicshop/category-bar', 'divimagicshop/spotlight',
		'divimagicshop/shop-filters', 'divimagicshop/active-filters', 'divimagicshop/faq-list', 'divimagicshop/tutorial-video', 'divimagicshop/tutorial-products', 'divimagicshop/product-tutorials']
		.forEach(function (name) {
			registerBlockType(name, {
				edit: function (props) {
					var postId = props.context && props.context.postId;
					return el('div', useBlockProps(), preview(name, props, postId ? { post_id: postId } : {}));
				},
				save: function () { return null; },
			});
		});
})(window.wp);
