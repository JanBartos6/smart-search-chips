(function (blocks, blockEditor, components, element, i18n) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;

    blocks.registerBlockType('smart-search-chips/search', {
        edit: function (props) {
            var placeholder = props.attributes.placeholder || __('Search...', 'smart-search-chips');
            var blockProps = useBlockProps({
                className: 'ssc-search-block-editor'
            });

            function updatePlaceholder(value) {
                props.setAttributes({
                    placeholder: value
                });
            }

            return el(
                element.Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        {
                            title: __('Search Settings', 'smart-search-chips')
                        },
                        el(TextControl, {
                            label: __('Placeholder', 'smart-search-chips'),
                            value: placeholder,
                            onChange: updatePlaceholder
                        })
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(
                        'div',
                        {
                            className: 'ssc-search',
                            'data-sschp-mode': 'guaranteed'
                        },
                        el(
                            'form',
                            {
                                className: 'ssc-search__form js-ssc-search',
                                role: 'search'
                            },
                            el('input', {
                                type: 'search',
                                className: 'ssc-search__input',
                                placeholder: placeholder,
                                disabled: true
                            }),
                            el(
                                'button',
                                {
                                    type: 'button',
                                    className: 'ssc-search__button',
                                    'aria-label': __('Search', 'smart-search-chips'),
                                    disabled: true
                                },
                                el(
                                    'svg',
                                    {
                                        width: 20,
                                        height: 20,
                                        viewBox: '0 0 24 24',
                                        fill: 'none',
                                        stroke: 'currentColor',
                                        strokeWidth: 2
                                    },
                                    el('circle', {
                                        cx: 11,
                                        cy: 11,
                                        r: 8
                                    }),
                                    el('path', {
                                        d: 'm21 21-4.35-4.35'
                                    })
                                )
                            )
                        ),
                        el('div', {
                            className: 'ssc-search__results js-ssc-results',
                            'aria-live': 'polite',
                            'aria-busy': 'false'
                        })
                    )
                )
            );
        },
        save: function () {
            return null;
        }
    });
}(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n));
