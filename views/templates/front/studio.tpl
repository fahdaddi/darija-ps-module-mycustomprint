{**
 * Custom Print Studio — "Print your own" quote-request page.
 *}
{extends file='page.tpl'}

{block name='page_title'}{l s='Print your own' d='Modules.Mycustomprint.Shop'}{/block}

{block name='page_content'}
  <div class="mcp-studio">
    <div class="mcp-studio__intro">
      <div class="mcp-studio__intro-text">
        <span class="mcp-studio__eyebrow">{l s='One piece minimum' d='Modules.Mycustomprint.Shop'}</span>
        <h1 class="mcp-studio__title">{l s='Print your own' d='Modules.Mycustomprint.Shop'}</h1>
        <p class="mcp-studio__lede">
          {l s='Bring artwork, or bring a sentence and let the studio draw it. Same presses, same blanks, same workshop — one piece is a valid order.' d='Modules.Mycustomprint.Shop'}
        </p>
      </div>
      <div class="mcp-studio__steps">
        <div class="mcp-studio__step">
          <span class="mcp-studio__step-num">01</span>
          <span class="mcp-studio__step-label">{l s='Upload or brief' d='Modules.Mycustomprint.Shop'}</span>
        </div>
        <div class="mcp-studio__step">
          <span class="mcp-studio__step-num">02</span>
          <span class="mcp-studio__step-label">{l s='Proof in 1 day' d='Modules.Mycustomprint.Shop'}</span>
        </div>
        <div class="mcp-studio__step">
          <span class="mcp-studio__step-num">03</span>
          <span class="mcp-studio__step-label">{l s='Printed in 2–4' d='Modules.Mycustomprint.Shop'}</span>
        </div>
      </div>
    </div>

    {if $form_errors}
      <div class="mcp-studio__alert mcp-studio__alert--error" role="alert">
        <ul>
          {foreach from=$form_errors item=error}
            <li>{$error}</li>
          {/foreach}
        </ul>
      </div>
    {/if}

    {if $sent_ok}
      <div class="mcp-studio__alert mcp-studio__alert--success" role="status">
        {l s='Request sent. We reply within one working day with a proof. Nothing is charged until you approve it.' d='Modules.Mycustomprint.Shop'}
      </div>
    {/if}

    <form
      id="mcp-studio-form"
      class="mcp-studio__form"
      method="post"
      action="{$form_action}"
      enctype="multipart/form-data"
      data-blanks="{$blanks|@json_encode|escape:'html':'UTF-8'}"
      data-methods="{$methods|@json_encode|escape:'html':'UTF-8'}"
      data-back-fee="{$back_placement_fee}"
      data-currency="{$currency_label|escape:'html':'UTF-8'}"
    >
      <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}">
      <div class="mcp-studio__layout">
        {include file='module:mycustomprint/views/templates/front/_partials/preview.tpl'}
        {include file='module:mycustomprint/views/templates/front/_partials/form-fields.tpl'}
      </div>
    </form>

    <section class="mcp-studio__specs">
      <div class="mcp-studio__spec">
        <span class="mcp-studio__spec-head">{l s='File specs' d='Modules.Mycustomprint.Shop'}</span>
        <p>{l s='300 dpi, PNG/JPG/SVG/PDF/AI. Transparent background for anything but a full bleed.' d='Modules.Mycustomprint.Shop'}</p>
      </div>
      <div class="mcp-studio__spec">
        <span class="mcp-studio__spec-head">{l s='Rights' d='Modules.Mycustomprint.Shop'}</span>
        <p>{l s='Only upload artwork you own. We refuse third-party logos and copyrighted characters.' d='Modules.Mycustomprint.Shop'}</p>
      </div>
      <div class="mcp-studio__spec">
        <span class="mcp-studio__spec-head">{l s='Studio drafting' d='Modules.Mycustomprint.Shop'}</span>
        <p>{l s='No file, just an idea? Describe it in the brief — two revisions, yours to keep.' d='Modules.Mycustomprint.Shop'}</p>
      </div>
    </section>
  </div>
{/block}
