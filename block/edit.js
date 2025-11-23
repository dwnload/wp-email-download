/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n'

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor'

import { PanelBody, SelectControl, Button, Placeholder, Spinner } from '@wordpress/components'
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor'
import { useState, useEffect } from '@wordpress/element'
import apiFetch from '@wordpress/api-fetch'

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss'

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit ({ attributes, setAttributes }) {
  const { listId, listName, attachmentId, attachmentUrl } = attributes
  const [lists, setLists] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    apiFetch({ path: '/dwnload/v1/lists' })
      .then((fetchedLists) => {
        setLists(fetchedLists)
        setLoading(false)
      })
      .catch((err) => {
        setError(err.message)
        setLoading(false)
      })
  }, [])

  const blockProps = useBlockProps({
    className: 'mailchimp-subscription-form-editor',
  })

  const listOptions = [
    { label: __('Select a Mailchimp list...', 'email-download'), value: '' },
    ...lists.map((list) => ({
      label: list.name,
      value: list.id,
    })),
  ]

  const onSelectList = (value) => {
    const selectedList = lists.find((list) => list.id === value)
    setAttributes({
      listId: value,
      listName: selectedList ? selectedList.name : '',
    })
  }

  const onSelectMedia = (media) => {
    setAttributes({
      attachmentId: media.id,
      attachmentUrl: media.url,
    })
  }

  const onRemoveMedia = () => {
    setAttributes({
      attachmentId: 0,
      attachmentUrl: '',
    })
  }

  const hasValidConfiguration = listId && attachmentId

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Form Settings', 'email-download')}>
          {loading ? (
            <Spinner/>
          ) : error ? (
            <p style={{ color: '#d63638' }}>
              {__('Error loading lists: ', 'email-download')}
              {error}
            </p>
          ) : (
            <>
              <SelectControl
                label={__('Mailchimp List', 'email-download')}
                value={listId}
                options={listOptions}
                onChange={onSelectList}
                help={__(
                  'Select the Mailchimp list where subscribers will be added',
                  'email-download'
                )}
              />

              <div style={{ marginTop: '16px' }}>
                <label
                  style={{
                    display: 'block',
                    marginBottom: '8px',
                    fontWeight: '600',
                  }}
                >
                  {__('File Attachment', 'email-download')}
                </label>
                <MediaUploadCheck>
                  <MediaUpload
                    onSelect={onSelectMedia}
                    // allowedTypes={ [ 'image', 'application/pdf', 'application/zip' ] }
                    value={attachmentId}
                    render={({ open }) => (
                      <div>
                        {attachmentId ? (
                          <div className="attachment-preview">
                            <p>
                              <strong>
                                {__(
                                  'Selected file:',
                                  'email-download'
                                )}
                              </strong>
                              <br/>
                              <a
                                href={attachmentUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                {attachmentUrl.split('/').pop()}
                              </a>
                            </p>
                            <div className="button-group">
                              <Button variant="secondary" onClick={open}>
                                {__(
                                  'Change File',
                                  'email-download'
                                )}
                              </Button>
                              <Button
                                variant="link"
                                isDestructive
                                onClick={onRemoveMedia}
                              >
                                {__('Remove', 'email-download')}
                              </Button>
                            </div>
                          </div>
                        ) : (
                          <Button variant="secondary" onClick={open}>
                            {__('Select File', 'email-download')}
                          </Button>
                        )}
                      </div>
                    )}
                  />
                </MediaUploadCheck>
                <p className="description">
                  {__(
                    'Choose a file from the Media Library to include with subscriptions',
                    'email-download'
                  )}
                </p>
              </div>
            </>
          )}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {!hasValidConfiguration ? (
          <Placeholder
            icon="email"
            label={__('Mailchimp Subscription Form', 'email-download')}
            instructions={__(
              'Configure the form settings in the sidebar to get started.',
              'email-download'
            )}
          >
            <div className="configuration-status">
              <p>
                <strong>{__('Required Configuration:', 'email-download')}</strong>
              </p>
              <ul>
                <li>
                  {listId ? '✓' : '○'}{' '}
                  {__('Select a Mailchimp list', 'email-download')}
                </li>
                <li>
                  {attachmentId ? '✓' : '○'}{' '}
                  {__('Choose a file attachment', 'email-download')}
                </li>
              </ul>
            </div>
          </Placeholder>
        ) : (
          <div className="form-preview">
            <div className="preview-header">
              <h3>{__('Subscription Form Preview', 'email-download')}</h3>
              <div className="preview-config">
                <p>
                  <strong>{__('List:', 'email-download')}</strong> {listName}
                </p>
                <p>
                  <strong>{__('Attachment:', 'email-download')}</strong>{' '}
                  {attachmentUrl.split('/').pop()}
                </p>
              </div>
            </div>
            <div className="form-mockup">
              <input
                type="email"
                placeholder={__('Enter your email address', 'email-download')}
                disabled
              />
              <button type="button" disabled>
                {__('Subscribe', 'email-download')}
              </button>
            </div>
            <p className="preview-note">
              {__(
                'This is a preview. The actual form will be interactive on the frontend.',
                'email-download'
              )}
            </p>
          </div>
        )}
      </div>
    </>
  )
}
