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

  const [mode, setMode] = useState(initialFile ? 'selected' : 'search');
  const [selected, setSelected] = useState(initialFile);
  const [caption, setCaption] = useState(initialFile ? initialFile.caption || '' : '');
  const [altText, setAltText] = useState(initialFile ? initialFile.altText || '' : '');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const [noResults, setNoResults] = useState(false);

  const debouncedSearch = useMemo(
    () =>
      debounce(async (query) => {
        if (!query || query.length < 2) {
          setSearchResults([]);
          setNoResults(false);
          return;
        }
        setSearching(true);
        setNoResults(false);
        try {
          const res = await fetch(
            `${searchEndpoint}?q=${encodeURIComponent(query)}`,
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
    const newAltText = item.altText || item.title || '';
    setSelected(item);
    setCaption(newCaption);
    setAltText(newAltText);
    setMode('selected');
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

  const handleChange = () => {
    setMode('search');
    setSearchQuery('');
    setSearchResults([]);
    setNoResults(false);
  };

  const handleRemove = () => {
    // eslint-disable-next-line no-alert
    if (window.confirm('Bild wirklich entfernen?')) {
      setSelected(null);
      setCaption('');
      setAltText('');
      setMode('search');
      onChange('');
    }
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

      {/* ── Search mode ── */}
      {mode === 'search' && (
        <div className="silvervault-file-field__search">
          <div className="input-group mb-2">
            <input
              type="text"
              className="form-control"
              placeholder="In Silvervault suchen…"
              value={searchQuery}
              onChange={handleSearchChange}
              disabled={disabled}
            />
          </div>

          {searching && (
            <p className="silvervault-file-field__status text-muted">
              Suche läuft…
            </p>
          )}

          {noResults && !searching && (
            <p className="silvervault-file-field__no-results text-muted">
              Keine Ergebnisse für &bdquo;{searchQuery}&ldquo;
            </p>
          )}

          {searchResults.length > 0 && (
            <ul className="silvervault-file-field__results list-unstyled">
              {searchResults.map((item) => (
                <li key={item.silvervaultId} className="silvervault-file-field__result">
                  {item.thumbnail && (
                    <div className="silvervault-file-field__result-thumb">
                      <img src={item.thumbnail} alt={item.title} />
                    </div>
                  )}
                  <div className="silvervault-file-field__result-info">
                    <div className="silvervault-file-field__result-title">
                      {item.title}
                    </div>
                    {item.description && (
                      <div className="silvervault-file-field__result-description text-muted small">
                        {item.description}
                      </div>
                    )}
                    {item.rightsinfo && (
                      <div className="silvervault-file-field__result-rights text-muted small">
                        <em>{item.rightsinfo}</em>
                      </div>
                    )}
                  </div>
                  <div className="silvervault-file-field__result-action">
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
      )}

      {/* ── Selected mode ── */}
      {mode === 'selected' && selected && (
        <div className="silvervault-file-field__selected">
          <div className="silvervault-file-field__preview">
            {selected.thumbnail && (
              <div className="silvervault-file-field__preview-thumb">
                <img src={selected.thumbnail} alt={selected.title} />
              </div>
            )}
            <div className="silvervault-file-field__preview-meta">
              {selected.title && (
                <div className="silvervault-file-field__preview-title">
                  <strong>{selected.title}</strong>
                </div>
              )}
              {selected.description && (
                <div className="silvervault-file-field__preview-description text-muted small">
                  {selected.description}
                </div>
              )}
              {selected.rightsinfo && (
                <div className="silvervault-file-field__preview-rights text-muted small">
                  <em>{selected.rightsinfo}</em>
                </div>
              )}
            </div>
          </div>

          <div className="silvervault-file-field__overrides">
            <div className="form-group">
              <label htmlFor={`${id}_caption`} className="form-label">
                Bildunterschrift
              </label>
              <textarea
                id={`${id}_caption`}
                className="form-control"
                value={caption}
                onChange={handleCaptionChange}
                rows={2}
                disabled={disabled}
              />
            </div>

            <div className="form-group mt-2">
              <label htmlFor={`${id}_alttext`} className="form-label">
                Alt-Text
              </label>
              <input
                type="text"
                id={`${id}_alttext`}
                className="form-control"
                value={altText}
                onChange={handleAltTextChange}
                disabled={disabled}
              />
            </div>
          </div>

          <div className="silvervault-file-field__actions mt-2">
            <button
              type="button"
              className="btn btn-outline-secondary btn-sm"
              onClick={handleChange}
              disabled={disabled}
            >
              Ändern
            </button>
            {' '}
            <button
              type="button"
              className="btn btn-outline-danger btn-sm"
              onClick={handleRemove}
              disabled={disabled}
            >
              Entfernen
            </button>
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
