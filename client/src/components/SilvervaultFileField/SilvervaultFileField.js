import React, { useState, useCallback } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import { loadComponent } from 'lib/Injector';

const SilvervaultFileField = ({ title, value, data, onChange }) => {
  const { searchFormEndpoint, searchEndpoint, silvervaultBaseUrl, vaultFile } = data || {};

  const parseValue = (val) => {
    if (!val) return null;
    if (typeof val === 'string') {
      try {
        const parsed = JSON.parse(val);
        return parsed && parsed.silvervaultId ? parsed : null;
      } catch (e) {
        return null;
      }
    }
    return val && val.silvervaultId ? val : null;
  };

  const initial = parseValue(value) || (vaultFile && vaultFile.silvervaultId ? vaultFile : null);
  const [selected, setSelected] = useState(initial);
  const [caption, setCaption] = useState(initial?.caption || '');
  const [altText, setAltText] = useState(initial?.altText || '');
  const [rightsOverride, setRightsOverride] = useState(initial?.rightsOverride || '');
  const [modalOpen, setModalOpen] = useState(false);

  const emitChange = useCallback((sel, cap, alt, rights) => {
    if (!sel) {
      onChange('');
      return;
    }
    onChange(JSON.stringify({
      silvervaultId: sel.silvervaultId,
      title: sel.title || '',
      description: sel.description || '',
      rightsinfo: sel.rightsinfo || '',
      thumbnail: sel.thumbnail || '',
      caption: cap,
      altText: alt,
      rightsOverride: rights,
    }));
  }, [onChange]);

  const handleSelect = useCallback((selectedData) => {
    if (!selectedData || !selectedData.silvervaultId) return;
    setSelected(selectedData);
    setCaption('');
    setAltText('');
    setRightsOverride('');
    setModalOpen(false);
    emitChange(selectedData, '', '', '');
  }, [emitChange]);

  const handleRemove = () => {
    setSelected(null);
    setCaption('');
    setAltText('');
    setRightsOverride('');
    emitChange(null, '', '', '');
  };

  const handleCaptionChange = (e) => {
    setCaption(e.target.value);
    emitChange(selected, e.target.value, altText, rightsOverride);
  };

  const handleAltTextChange = (e) => {
    setAltText(e.target.value);
    emitChange(selected, caption, e.target.value, rightsOverride);
  };

  const handleRightsChange = (e) => {
    setRightsOverride(e.target.value);
    emitChange(selected, caption, altText, e.target.value);
  };

  // Render modal via React portal
  const renderModal = () => {
    if (!modalOpen) return null;

    const CmsModal = loadComponent('CmsModal');
    const CmsModalSearch = loadComponent('CmsModalSearch');

    const modalData = {
      formEndpoint: searchFormEndpoint,
      searchEndpoint,
    };

    let portal = document.getElementById('cms-modal-portal');
    if (!portal) {
      portal = document.createElement('div');
      portal.id = 'cms-modal-portal';
      document.body.appendChild(portal);
    }

    return ReactDOM.createPortal(
      <CmsModal
        title="Select image from Silvervault"
        size="md"
        onClose={() => setModalOpen(false)}
      >
        <CmsModalSearch
          data={modalData}
          onSelect={handleSelect}
          onClose={() => setModalOpen(false)}
        />
      </CmsModal>,
      portal
    );
  };

  return (
    <div className="silvervault-file-field">
      {title && (
        <label className="form-label" style={{ fontWeight: 600, display: 'block', marginBottom: '8px' }}>
          {title}
        </label>
      )}

      {selected && (
        <div style={{ border: '1px solid #dee2e6', borderRadius: '4px', padding: '12px', marginBottom: '12px' }}>
          <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start', marginBottom: '12px' }}>
            {selected.thumbnail && (
              <img
                src={selected.thumbnail}
                alt={selected.title || ''}
                style={{ width: '80px', height: '80px', objectFit: 'cover', borderRadius: '4px', flexShrink: 0 }}
              />
            )}
            <div style={{ flex: 1, minWidth: 0 }}>
              <strong>{selected.title}</strong>
              {selected.description && (
                <div style={{ color: '#6c757d', fontSize: '0.85em', marginTop: '2px' }}>
                  {selected.description.length > 120 ? selected.description.substring(0, 120) + '...' : selected.description}
                </div>
              )}
              {selected.rightsinfo && (
                <div style={{ color: '#6c757d', fontSize: '0.85em', fontStyle: 'italic', marginTop: '2px' }}>
                  {selected.rightsinfo}
                </div>
              )}
              {silvervaultBaseUrl && (
                <a
                  href={`${silvervaultBaseUrl}/media/view/${selected.silvervaultId}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ fontSize: '0.8em' }}
                >
                  View in Silvervault
                </a>
              )}
            </div>
          </div>

          <div style={{ borderTop: '1px solid #dee2e6', paddingTop: '10px', marginTop: '4px' }}>
            <label style={{ display: 'block', fontWeight: 600, marginBottom: '2px', fontSize: '0.9em' }}>Caption</label>
            <textarea
              className="form-control"
              rows="2"
              style={{ marginBottom: '8px' }}
              value={caption}
              onChange={handleCaptionChange}
            />
            <label style={{ display: 'block', fontWeight: 600, marginBottom: '2px', fontSize: '0.9em' }}>Alt text</label>
            <input
              type="text"
              className="form-control"
              style={{ marginBottom: '8px' }}
              value={altText}
              onChange={handleAltTextChange}
            />
            <label style={{ display: 'block', fontWeight: 600, marginBottom: '2px', fontSize: '0.9em' }}>Source</label>
            <input
              type="text"
              className="form-control"
              value={rightsOverride}
              onChange={handleRightsChange}
            />
          </div>

          <div style={{ display: 'flex', gap: '8px', marginTop: '12px', paddingTop: '10px', borderTop: '1px solid #dee2e6' }}>
            <button
              type="button"
              className="btn btn-outline-secondary btn-sm"
              onClick={() => setModalOpen(true)}
            >
              Change
            </button>
            <button
              type="button"
              className="btn btn-outline-danger btn-sm"
              onClick={handleRemove}
            >
              Remove
            </button>
          </div>
        </div>
      )}

      {!selected && (
        <button
          type="button"
          className="btn btn-outline-secondary btn-sm"
          onClick={() => setModalOpen(true)}
        >
          Select
        </button>
      )}

      {renderModal()}
    </div>
  );
};

SilvervaultFileField.propTypes = {
  title: PropTypes.string,
  value: PropTypes.string,
  data: PropTypes.shape({
    searchFormEndpoint: PropTypes.string,
    searchEndpoint: PropTypes.string,
    silvervaultBaseUrl: PropTypes.string,
    vaultFile: PropTypes.object,
  }),
  onChange: PropTypes.func,
};

SilvervaultFileField.defaultProps = {
  title: '',
  value: '',
  data: {},
  onChange: () => {},
};

export default SilvervaultFileField;
