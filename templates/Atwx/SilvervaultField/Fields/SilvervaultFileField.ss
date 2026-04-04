<div class="silvervault-file-field" id="$ID">
    <input type="hidden" name="$Name" value="<% if $VaultFile %>$VaultFile.SilvervaultID<% end_if %>" />

    <% if $VaultFile %>
    <div class="silvervault-preview" style="display: flex; gap: 12px; align-items: flex-start; padding: 8px 0; margin-bottom: 12px;">
        <% if $VaultFile.ThumbnailURL %>
        <img src="$VaultFile.ThumbnailURL" alt="$VaultFile.Title.ATT" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; flex-shrink: 0;" />
        <% end_if %>
        <div style="flex: 1; min-width: 0;">
            <strong class="silvervault-preview-title">$VaultFile.Title</strong>
            <% if $VaultFile.Description %>
            <div style="color: #6c757d; font-size: 0.85em; margin-top: 2px;">$VaultFile.Description.LimitCharacters(120)</div>
            <% end_if %>
            <% if $VaultFile.Rightsinfo %>
            <div style="color: #6c757d; font-size: 0.85em; font-style: italic; margin-top: 2px;">$VaultFile.Rightsinfo</div>
            <% end_if %>
            <% if $SilvervaultBaseUrl %>
            <a href="$SilvervaultBaseUrl/media/view/$VaultFile.SilvervaultID" target="_blank" rel="noopener" style="font-size: 0.8em;">In Silvervault ansehen</a>
            <% end_if %>
        </div>
    </div>

    <div class="silvervault-fields" style="margin-bottom: 12px;">
        <div class="form-group" style="margin-bottom: 8px;">
            <label class="form-label" for="${ID}_Caption"><%t Atwx\SilvervaultField\Fields\SilvervaultFileField.CAPTION 'Bildunterschrift' %></label>
            <textarea name="${Name}_Caption" id="${ID}_Caption" class="form-control" rows="2">$VaultFile.Caption</textarea>
        </div>
        <div class="form-group" style="margin-bottom: 8px;">
            <label class="form-label" for="${ID}_AltText"><%t Atwx\SilvervaultField\Fields\SilvervaultFileField.ALTTEXT 'Alt-Text' %></label>
            <input type="text" name="${Name}_AltText" id="${ID}_AltText" class="form-control" value="$VaultFile.AltText.ATT" />
        </div>
        <div class="form-group" style="margin-bottom: 8px;">
            <label class="form-label" for="${ID}_RightsOverride"><%t Atwx\SilvervaultField\Fields\SilvervaultFileField.RIGHTS 'Quelle' %></label>
            <input type="text" name="${Name}_RightsOverride" id="${ID}_RightsOverride" class="form-control" value="$VaultFile.RightsOverride.ATT" />
        </div>
    </div>
    <% end_if %>

    <div style="display: flex; gap: 8px;">
        <button type="button"
                class="btn btn-secondary btn-sm cms-modal-action"
                data-modal-component="CmsModalSearch"
                data-modal-title="<%t Atwx\SilvervaultField\Fields\SilvervaultFileField.MODAL_TITLE 'Bild aus Silvervault auswählen' %>"
                data-modal-data="$ModalDataJSON"
                data-modal-size="md">
            <% if $VaultFile %>
                <%t Atwx\SilvervaultField\Fields\SilvervaultFileField.CHANGE 'Ändern' %>
            <% else %>
                <%t Atwx\SilvervaultField\Fields\SilvervaultFileField.SELECT 'Auswählen' %>
            <% end_if %>
        </button>
        <% if $VaultFile %>
        <button type="button" class="btn btn-outline-danger btn-sm silvervault-remove">
            <%t Atwx\SilvervaultField\Fields\SilvervaultFileField.REMOVE 'Entfernen' %>
        </button>
        <% end_if %>
    </div>
</div>
