{**
 * Sticky live-mockup preview: blank product photo + artwork overlay + print-area box.
 * Positioning and artwork swap are handled by front.js; this just lays out the
 * DOM structure and data hooks it reads (data-mcp-* attributes).
 *}
<div class="mcp-studio__preview">
  <div class="mcp-studio__frame" data-mcp-frame>
    {foreach from=$blanks item=blank}
      {if $blank.image_front_url}
        <img
          class="mcp-studio__blank-photo"
          data-mcp-blank-photo="{$blank.id|escape:'html':'UTF-8'}"
          data-mcp-blank-side="front"
          src="{$blank.image_front_url|escape:'html':'UTF-8'}"
          alt="{$blank.label|escape:'html':'UTF-8'}"
          hidden
        >
      {/if}
      {if $blank.image_back_url}
        <img
          class="mcp-studio__blank-photo"
          data-mcp-blank-photo="{$blank.id|escape:'html':'UTF-8'}"
          data-mcp-blank-side="back"
          src="{$blank.image_back_url|escape:'html':'UTF-8'}"
          alt="{$blank.label|escape:'html':'UTF-8'}"
          hidden
        >
      {/if}
    {/foreach}
    <div class="mcp-studio__blank-placeholder" data-mcp-blank-placeholder>
      {l s='Blank product photo' d='Modules.Mycustomprint.Shop'}
    </div>

    <img class="mcp-studio__artwork" data-mcp-artwork-preview alt="{l s='Your artwork' d='Modules.Mycustomprint.Shop'}" hidden>

    <div class="mcp-studio__print-area" data-mcp-print-area>
      <span class="mcp-studio__print-area-label">{l s='Print area' d='Modules.Mycustomprint.Shop'}</span>
    </div>
  </div>

  <div class="mcp-studio__preview-controls">
    <span class="mcp-studio__mono" data-mcp-placement-label></span>
    <label class="mcp-studio__scale">
      <span class="mcp-studio__mono">{l s='Scale' d='Modules.Mycustomprint.Shop'}</span>
      <input type="range" min="24" max="92" value="62" data-mcp-scale>
    </label>
  </div>
</div>
