import React, { useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import fieldHolder from 'components/FieldHolder/FieldHolder';

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function parseValue(rawValue) {
  if (!rawValue) return null;
  try {
    const parsed = JSON.parse(rawValue);
    return parsed && parsed.silvervaultId ? parsed : null;
  } catch (e) {
    return null;
  }
}

function buildJsonValue(selected, caption, altText) {
  if (!selected || !selected.silvervaultId) return '';
  return JSON.stringify({
    silvervaultId: selected.silvervaultId,
    title: selected.title || '',
    description: selected.description || '',
    rightsinfo: selected.rightsinfo || '',
    thumbnail: selected.thumbnail || '',
    caption,
    altText,
  });
}

const SilvervaultFileField = ({
  id,
  name,
  value: rawValue,
  onChange,
  data,
  disabled,
  readOnly,
}) => {
  const { searchEndpoint } = data || {};

  const initialFile = parseValue(rawValue);

  const [selected, setSelected] = useState(initialFile);
  const [caption, setCaption] = useState(initialFile ? initialFile.caption || '' : '');
  const [altText, setAltText] = useState(initialFile ? initialFile.altText || '' : '');
  const [popupOpen, setPopupOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [noResults, setNoResults] = useState(false);

  const debouncedSearch = useMemo(
    () =>
      debounce(async (query) => {
        const trimmed = query ? query.trim() : '';
        const isIdSearch = /^\d+$/.test(trimmed);
        if (!trimmed || (!isIdSearch && trimmed.length < 2)) {
          setSearchResults([]);
          setNoResults(false);
          return;
        }
        setSearching(true);
        setNoResults(false);
        const searchParam = isIdSearch
          ? `id=${encodeURIComponent(trimmed)}`
          : `q=${encodeURIComponent(query)}`;
        try {
          const res = await fetch(
            `${searchEndpoint}?${searchParam}`,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
          );
          const json = await res.json();
          const items = json.items || [];
          setSearchResults(items);
          setNoResults(items.length === 0);
        } catch (e) {
          setSearchResults([]);
          setNoResults(false);
        } finally {
          setSearching(false);
        }
      }, 300),
    [searchEndpoint]
  );

  const handleSearchChange = (e) => {
    const q = e.target.value;
    setSearchQuery(q);
    debouncedSearch(q);
  };

  const handleSelect = (item) => {
    const newCaption = item.caption || '';
    const newAltText = '';
    setSelected(item);
    setCaption(newCaption);
    setAltText(newAltText);
    setPopupOpen(false);
    setSearchQuery('');
    setSearchResults([]);
    setNoResults(false);
    onChange(buildJsonValue(item, newCaption, newAltText));
  };

  const handleCaptionChange = (e) => {
    const val = e.target.value;
    setCaption(val);
    onChange(buildJsonValue(selected, val, altText));
  };

  const handleAltTextChange = (e) => {
    const val = e.target.value;
    setAltText(val);
    onChange(buildJsonValue(selected, caption, val));
  };

  const handleOpenPopup = () => {
    setPopupOpen(true);
    setSearchQuery('');
    setSearchResults([]);
    setNoResults(false);
  };

  const handleClosePopup = () => {
    setPopupOpen(false);
    setSearchQuery('');
    setSearchResults([]);
    setNoResults(false);
  };

  const handleRemove = () => {
    setSelected(null);
    setCaption('');
    setAltText('');
    onChange('');
  };

  // Read-only rendering
  if (readOnly) {
    if (!selected) {
      return <span className="silvervault-file-field--empty">&mdash;</span>;
    }
    return (
      <div className="silvervault-file-field silvervault-file-field--readonly">
        {selected.thumbnail && (
          <img
            src={selected.thumbnail}
            alt={altText || selected.title}
            className="silvervault-file-field__thumbnail"
          />
        )}
        <div className="silvervault-file-field__meta">
          {selected.title && (
            <div className="silvervault-file-field__title">
              <strong>{selected.title}</strong>
            </div>
          )}
          {caption && (
            <div className="silvervault-file-field__caption">{caption}</div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="silvervault-file-field">
      <input type="hidden" name={name} id={id} value={buildJsonValue(selected, caption, altText)} readOnly />

      {/* ── Empty state: just a button ── */}
      {!selected && (
        <button
          type="button"
          className="btn btn-outline-secondary btn-sm"
          onClick={handleOpenPopup}
          disabled={disabled}
        >
          Auswählen
        </button>
      )}

      {/* ── Selected state: compact row like SilverStripe UploadField ── */}
      {selected && (
        <div className="silvervault-file-field__selected">
          {/* Row box: thumbnail | info | action buttons */}
          <div style={{
            display: 'flex',
            alignItems: 'center',
            border: '1px solid #dee2e6',
            borderRadius: '3px',
            gap: '12px',
            background: '#fff',
          }}>
            {/* Thumbnail */}
            <div style={{
              width: '60px',
              height: '60px',
              flexShrink: 0,
              background: '#f5f5f5',
              overflow: 'hidden',
              borderRadius: '2px',
            }}>
              {selected.thumbnail && (
                <img
                  src={selected.thumbnail}
                  alt={altText || selected.title}
                  style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block' }}
                />
              )}
            </div>

            {/* Info text */}
            <div style={{ flex: 1, minWidth: 0 }}>
              {selected.title && (
                <div style={{ fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {selected.title}
                </div>
              )}
              {selected.description && (
                <div style={{ color: '#6c757d', fontSize: '0.875rem', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {selected.description}
                </div>
              )}
              {selected.silvervaultId && (
                <div style={{ color: '#6c757d', fontSize: '0.8rem' }}>
                  ID: {selected.silvervaultId}
                </div>
              )}
            </div>

            {/* Action buttons */}
            <div style={{ flexShrink: 0, display: 'flex', gap: '4px' }}>
              <a
                href={`${data.silvervaultBaseUrl || ''}/media/view/${selected.silvervaultId}`}
                target="_blank"
                rel="noreferrer"
                className="btn btn-outline-secondary btn-sm"
                title="In Silvervault ansehen"
              >
                Info
              </a>
              <button
                type="button"
                className="btn btn-outline-danger btn-sm"
                onClick={handleRemove}
                disabled={disabled}
                title="Entfernen"
              >
                Entfernen
              </button>
            </div>
          </div>

          {/* Text fields below the row */}
          <div className="silvervault-file-field__overrides" style={{ marginTop: '12px' }}>
            <div className="form-group">
              <label htmlFor={`${id}_caption`} className="form__field-label">
                Bildunterschrift
              </label>
              <div className="form__field-holder">
                <textarea
                  id={`${id}_caption`}
                  className="form-control"
                  value={caption}
                  placeholder={selected.title || ''}
                  onChange={handleCaptionChange}
                  rows={2}
                  disabled={disabled}
                />
              </div>
            </div>

            <div className="form-group">
              <label htmlFor={`${id}_alttext`} className="form__field-label">
                Alt-Text
              </label>
              <div className="form__field-holder">
                <input
                  type="text"
                  id={`${id}_alttext`}
                  className="form-control"
                  value={altText}
                  placeholder={selected.title || ''}
                  onChange={handleAltTextChange}
                  disabled={disabled}
                />
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ── Search popup/modal ── */}
      {popupOpen && (
        <div
          style={{
            position: 'fixed',
            top: 0, left: 0, right: 0, bottom: 0,
            backgroundColor: 'rgba(0,0,0,0.5)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'flex-start',
            justifyContent: 'center',
            paddingTop: '60px',
          }}
          onClick={handleClosePopup}
        >
          <div
            style={{
              backgroundColor: '#fff',
              borderRadius: '4px',
              width: '640px',
              maxWidth: '90vw',
              maxHeight: '80vh',
              display: 'flex',
              flexDirection: 'column',
              boxShadow: '0 4px 24px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div style={{ padding: '16px 20px', borderBottom: '1px solid #dee2e6', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <strong>Bild aus Silvervault auswählen</strong>
              <button
                type="button"
                className="btn-close"
                onClick={handleClosePopup}
                aria-label="Schließen"
              />
            </div>

            {/* Search input */}
            <div style={{ padding: '16px 20px', borderBottom: '1px solid #dee2e6' }}>
              <input
                type="text"
                className="form-control"
                placeholder="In Silvervault suchen…"
                value={searchQuery}
                onChange={handleSearchChange}
                // eslint-disable-next-line jsx-a11y/no-autofocus
                autoFocus
                disabled={disabled}
              />
            </div>

            {/* Results list */}
            <div style={{ overflowY: 'auto', flex: 1 }}>
              {searching && (
                <p style={{ padding: '16px 20px', margin: 0 }} className="text-muted">
                  Suche läuft…
                </p>
              )}
              {noResults && !searching && (
                <p style={{ padding: '16px 20px', margin: 0 }} className="text-muted">
                  Keine Ergebnisse für „{searchQuery}"
                </p>
              )}
              {!searching && searchResults.length === 0 && searchQuery.length < 2 && (
                <p style={{ padding: '16px 20px', margin: 0 }} className="text-muted">
                  Suchbegriff eingeben (mind. 2 Zeichen)…
                </p>
              )}
              {searchResults.length > 0 && (
                <ul className="list-unstyled" style={{ margin: 0 }}>
                  {searchResults.map((item) => (
                    <li
                      key={item.silvervaultId}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        padding: '10px 20px',
                        borderBottom: '1px solid #f0f0f0',
                        gap: '12px',
                      }}
                    >
                      {/* Small square thumbnail */}
                      <div
                        style={{
                          width: '56px',
                          height: '56px',
                          flexShrink: 0,
                          background: '#f5f5f5',
                          overflow: 'hidden',
                          borderRadius: '3px',
                        }}
                      >
                        {item.thumbnail && (
                          <img
                            src={item.thumbnail}
                            alt={item.title}
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                          />
                        )}
                      </div>

                      {/* Info text */}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {item.title}
                        </div>
                        {item.description && (
                          <div className="text-muted small" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {item.description}
                          </div>
                        )}
                        {item.rightsinfo && (
                          <div className="text-muted small">
                            <em>{item.rightsinfo}</em>
                          </div>
                        )}
                        {item.silvervaultId && (
                          <div className="text-muted" style={{ fontSize: '0.8rem' }}>
                            ID: {item.silvervaultId}
                          </div>
                        )}
                      </div>

                      {/* Select button */}
                      <div style={{ flexShrink: 0 }}>
                        <button
                          type="button"
                          className="btn btn-primary btn-sm"
                          onClick={() => handleSelect(item)}
                          disabled={disabled}
                        >
                          Auswählen
                        </button>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            {/* Footer */}
            <div style={{ padding: '12px 20px', borderTop: '1px solid #dee2e6', textAlign: 'right' }}>
              <button
                type="button"
                className="btn btn-secondary btn-sm"
                onClick={handleClosePopup}
              >
                Schließen
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

SilvervaultFileField.propTypes = {
  id: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
  value: PropTypes.string,
  onChange: PropTypes.func.isRequired,
  data: PropTypes.shape({
    searchEndpoint: PropTypes.string,
  }),
  disabled: PropTypes.bool,
  readOnly: PropTypes.bool,
};

SilvervaultFileField.defaultProps = {
  value: '',
  data: {},
  disabled: false,
  readOnly: false,
};

export default fieldHolder(SilvervaultFileField);
