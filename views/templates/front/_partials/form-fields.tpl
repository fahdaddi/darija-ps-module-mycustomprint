{**
 * Right-hand column: upload, blank picker, placement/method, brief, contact, summary.
 *}
<div class="mcp-studio__fields">

  <section class="mcp-studio__block" data-mcp-block="1">
    <div class="mcp-studio__block-head">
      <span class="mcp-studio__mono">01</span>
      <h2 class="mcp-studio__block-title">{l s='Upload your artwork' d='Modules.Mycustomprint.Shop'}</h2>
    </div>
    <div class="mcp-studio__dropzone" data-mcp-dropzone>
      <span class="mcp-studio__dropzone-label" data-mcp-dropzone-label>{l s='Drop a file or browse' d='Modules.Mycustomprint.Shop'}</span>
      <span class="mcp-studio__dropzone-hint" data-mcp-dropzone-hint>{l s='PNG, JPG, SVG, PDF or AI · 300 dpi · up to' d='Modules.Mycustomprint.Shop'} {$max_upload_label|escape:'html':'UTF-8'}</span>
      <input type="file" name="artwork" accept="image/*,.pdf,.ai,.svg" data-mcp-file-input>
    </div>
    <span class="mcp-studio__mono mcp-studio__help">
      {l s='No file? Describe it below and our studio drafts it from your brief.' d='Modules.Mycustomprint.Shop'}
    </span>
  </section>

  <section class="mcp-studio__block" data-mcp-block="2">
    <div class="mcp-studio__block-head">
      <span class="mcp-studio__mono">02</span>
      <h2 class="mcp-studio__block-title">{l s='Choose a blank' d='Modules.Mycustomprint.Shop'}</h2>
    </div>
    <div class="mcp-studio__blank-grid" data-mcp-blank-grid>
      {foreach from=$blanks item=blank name=blanks}
        <button type="button" class="mcp-studio__blank-option{if $smarty.foreach.blanks.first} mcp-studio__blank-option--active{/if}" data-mcp-blank-option data-blank-id="{$blank.id|escape:'html':'UTF-8'}" data-blank-base="{$blank.base}">
          <span class="mcp-studio__blank-option-head">
            <span class="mcp-studio__blank-dot"></span>
            {$blank.label|escape:'html':'UTF-8'}
          </span>
          <span class="mcp-studio__blank-option-note">{$blank.note|escape:'html':'UTF-8'} · {l s='from' d='Modules.Mycustomprint.Shop'} {$blank.base} {$currency_label|escape:'html':'UTF-8'}</span>
        </button>
      {/foreach}
    </div>
    <input type="hidden" name="blank" data-mcp-blank-value value="{$blanks[0].id|escape:'html':'UTF-8'}">

    <div class="mcp-studio__size-row" data-mcp-size-row>
      {foreach from=$sizes item=size}
        <button type="button" class="mcp-studio__size-option{if $size == 'M'} mcp-studio__size-option--active{/if}" data-mcp-size-option data-size="{$size}">{$size}</button>
      {/foreach}
    </div>
    <input type="hidden" name="size" data-mcp-size-value value="M">
  </section>

  <section class="mcp-studio__block" data-mcp-block="3">
    <div class="mcp-studio__block-head">
      <span class="mcp-studio__mono">03</span>
      <h2 class="mcp-studio__block-title">{l s='Placement & method' d='Modules.Mycustomprint.Shop'}</h2>
    </div>
    <div class="mcp-studio__placement-row" data-mcp-placement-row>
      {foreach from=$placements item=placement name=placements}
        <button type="button" class="mcp-studio__placement-option{if $smarty.foreach.placements.first} mcp-studio__placement-option--active{/if}" data-mcp-placement-option data-placement-id="{$placement.id|escape:'html':'UTF-8'}">{$placement.label|escape:'html':'UTF-8'}</button>
      {/foreach}
    </div>
    <input type="hidden" name="placement" data-mcp-placement-value value="{$placements[0].id|escape:'html':'UTF-8'}">

    <div class="mcp-studio__method-list" data-mcp-method-list>
      {foreach from=$methods item=method name=methods}
        <label class="mcp-studio__method-option{if $smarty.foreach.methods.first} mcp-studio__method-option--active{/if}" data-mcp-method-option data-method-id="{$method.id|escape:'html':'UTF-8'}" data-method-fee="{$method.fee}">
          <span class="mcp-studio__method-radio"></span>
          <span class="mcp-studio__method-copy">
            <span class="mcp-studio__mono">{$method.label|escape:'html':'UTF-8'}</span>
            <span class="mcp-studio__method-note">{$method.note|escape:'html':'UTF-8'}</span>
          </span>
          <span class="mcp-studio__mono">{if $method.fee}+{$method.fee} {$currency_label|escape:'html':'UTF-8'}{else}{l s='incl.' d='Modules.Mycustomprint.Shop'}{/if}</span>
        </label>
      {/foreach}
    </div>
    <input type="hidden" name="method" data-mcp-method-value value="{$methods[0].id|escape:'html':'UTF-8'}">
  </section>

  <section class="mcp-studio__block mcp-studio__block--last" data-mcp-block="4">
    <div class="mcp-studio__block-head">
      <span class="mcp-studio__mono">04</span>
      <h2 class="mcp-studio__block-title">{l s='Brief, quantity & contact' d='Modules.Mycustomprint.Shop'}</h2>
    </div>

    <textarea name="brief" rows="4" class="mcp-studio__textarea" placeholder="{l s='Darija punchline, colours, references, deadline…' d='Modules.Mycustomprint.Shop'}"></textarea>

    <div class="mcp-studio__qty-row">
      <div class="mcp-studio__qty-stepper">
        <span class="mcp-studio__mono">{l s='Qty' d='Modules.Mycustomprint.Shop'}</span>
        <div class="mcp-studio__qty-control">
          <button type="button" data-mcp-qty-decrement aria-label="{l s='Decrease quantity' d='Modules.Mycustomprint.Shop'}">−</button>
          <span data-mcp-qty-value>1</span>
          <button type="button" data-mcp-qty-increment aria-label="{l s='Increase quantity' d='Modules.Mycustomprint.Shop'}">+</button>
        </div>
      </div>
      <span class="mcp-studio__mono" data-mcp-qty-hint>{l s='10+ gets a bulk rate' d='Modules.Mycustomprint.Shop'}</span>
      <input type="hidden" name="quantity" data-mcp-qty-input value="1">
    </div>

    <div class="mcp-studio__contact-grid">
      <input type="text" name="firstname" class="mcp-studio__input" placeholder="{l s='First name' d='Modules.Mycustomprint.Shop'}" required>
      <input type="text" name="lastname" class="mcp-studio__input" placeholder="{l s='Last name' d='Modules.Mycustomprint.Shop'}" required>
      <input type="email" name="email" class="mcp-studio__input" placeholder="{l s='Email' d='Modules.Mycustomprint.Shop'}" required>
      <input type="tel" name="phone" class="mcp-studio__input" placeholder="{l s='Phone (optional)' d='Modules.Mycustomprint.Shop'}">
    </div>

    <div class="mcp-studio__summary">
      <div class="mcp-studio__summary-row">
        <span class="mcp-studio__mono">{l s='Blank' d='Modules.Mycustomprint.Shop'} — <span data-mcp-summary-blank></span></span>
        <span class="mcp-studio__mono" data-mcp-summary-blank-price></span>
      </div>
      <div class="mcp-studio__summary-row">
        <span class="mcp-studio__mono">{l s='Method' d='Modules.Mycustomprint.Shop'}</span>
        <span class="mcp-studio__mono" data-mcp-summary-method-fee></span>
      </div>
      <div class="mcp-studio__summary-row">
        <span class="mcp-studio__mono">{l s='Placement' d='Modules.Mycustomprint.Shop'}</span>
        <span class="mcp-studio__mono" data-mcp-summary-placement-fee></span>
      </div>
      <div class="mcp-studio__summary-total">
        <span class="mcp-studio__mono">{l s='Estimate' d='Modules.Mycustomprint.Shop'} · <span data-mcp-summary-qty>1</span>×</span>
        <span class="mcp-studio__summary-total-value"><span data-mcp-summary-total>0</span> {$currency_label|escape:'html':'UTF-8'}</span>
      </div>
    </div>
    <input type="hidden" name="estimated_total" data-mcp-estimated-total-input value="0">

    <div class="mcp-studio__submit-row">
      <button type="submit" name="submitPrintRequest" class="mcp-studio__submit">{l s='Send print request' d='Modules.Mycustomprint.Shop'}</button>
    </div>
    <span class="mcp-studio__mono mcp-studio__help">
      {l s='A studio proof comes back within one working day. No charge before you approve.' d='Modules.Mycustomprint.Shop'}
    </span>
  </section>
</div>
