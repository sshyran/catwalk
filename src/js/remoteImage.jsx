import React, { Component } from 'react'
import { connect } from 'react-redux'
import PropTypes from 'prop-types'

import { deprecate, omit, MediaApi } from '@frontastic/common'
import NoImage from '../layout/noImage.svg'
import sizer from './helper/reactSizer'

class RemoteImage extends Component {
    constructor (props) {
        super(props)

        this.state = {
            loading: true,
            error: false,
        }
    }

    mediaApi = new MediaApi()

    render () {
        if (typeof this.props.cropRatio === 'number') {
            deprecate('Numeric crop ratios are deprecated, please use a crop ratio like 3:4')
        }

        let [width, height] = this.mediaApi.getImageDimensions(
            this.props.url,
            this.props.width,
            this.props.height,
            this.props.cropRatio
        )

        if (this.state.error || !width || !height) {
            return (
                <img
                    style={this.props.style}
                    width={width}
                    height={height}
                    alt={this.props.alt}
                    // @TODO: Some blurred image would be great, because this
                    // can also happen during loading. But this is ahrd for
                    // random remote images:
                    src={NoImage}
                    {...omit(this.props, [
                        'context',
                        'url',
                        'alt',
                        'cropRatio',
                        'width',
                        'height',
                        'dispatch',
                        'options',
                    ])}
                />
            )
        }

        return (
            <img
                style={this.props.style}
                loading={this.props.loading}
                className={this.state.loading ? 'loading' : 'loaded'}
                onLoad={() => {
                    this.setState({ loading: false })
                }}
                width={width}
                height={height}
                alt={this.props.alt}
                src={this.mediaApi.getImageLink(
                    this.props.url,
                    this.props.context.project.configuration,
                    this.props.width,
                    this.props.height,
                    this.props.cropRatio,
                    this.props.options
                )}
                srcSet={[1, 2].map((factor) => {
                    return [
                        this.mediaApi.getImageLink(
                            this.props.url,
                            this.props.context.project.configuration,
                            this.props.width,
                            this.props.height,
                            this.props.cropRatio,
                            this.props.options
                        ),
                        factor + 'x',
                    ].join(' ')
                }).join(', ')}
                onError={() => {
                    this.setState({ error: true })
                }}
                {...omit(this.props, [
                    'context',
                    'url',
                    'alt',
                    'cropRatio',
                    'width',
                    'height',
                    'dispatch',
                    'options',
                ])}
            />
        )
    }
}

RemoteImage.propTypes = {
    context: PropTypes.object.isRequired,
    url: PropTypes.string.isRequired,
    alt: PropTypes.string.isRequired,
    width: PropTypes.number,
    height: PropTypes.number,
    loading: PropTypes.oneOf(['lazy', 'auto', 'eager']),
    style: PropTypes.object,
    cropRatio: PropTypes.oneOfType([
        PropTypes.string,
        // @DEPRECATED:
        PropTypes.number,
    ]).isRequired,
    options: PropTypes.object,
}

RemoteImage.defaultProps = {
    style: {},
    cropRatio: null,
    loading: 'lazy',
}

export default connect((globalState, props) => {
    return {
        ...props,
        context: globalState.app.context,
    }
})(
    sizer({
        getSize: MediaApi.getElementDimensions,
    })(RemoteImage)
)
