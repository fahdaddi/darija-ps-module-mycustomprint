<div class="panel">
  <form action="{$form_action}" method="post" id="mycustomprint-settings">
    <div class="panel-heading">
      <i class="icon icon-cogs"></i> {l s='Custom Print Studio settings' d='Modules.Mycustomprint.Shop'}
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Notification email' d='Modules.Mycustomprint.Shop'}</label>
      <div class="col-lg-6">
        <input type="email" name="notify_email" class="form-control" value="{$notify_email|escape:'html':'UTF-8'}" required>
        <p class="help-block">{l s='Staff address that receives every new print request.' d='Modules.Mycustomprint.Shop'}</p>
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Currency label' d='Modules.Mycustomprint.Shop'}</label>
      <div class="col-lg-2">
        <input type="text" name="currency_label" class="form-control" value="{$currency_label|escape:'html':'UTF-8'}" maxlength="8" required>
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Back placement surcharge' d='Modules.Mycustomprint.Shop'}</label>
      <div class="col-lg-2">
        <input type="number" step="0.01" min="0" name="back_placement_fee" class="form-control" value="{$back_placement_fee|escape:'html':'UTF-8'}">
      </div>
    </div>

    <hr>
    <h4>{l s='Blank products' d='Modules.Mycustomprint.Shop'}</h4>
    <p class="help-block">{l s='Up to 6 rows. Leave the label empty to skip a row.' d='Modules.Mycustomprint.Shop'}</p>
    <table class="table">
      <thead>
        <tr>
          <th>{l s='ID' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Label' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Base price' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Note' d='Modules.Mycustomprint.Shop'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$blanks item=blank name=blanks}
          <tr>
            <td><input type="text" name="blank_id_{$smarty.foreach.blanks.index}" class="form-control" value="{$blank.id|escape:'html':'UTF-8'}" placeholder="{l s='auto' d='Modules.Mycustomprint.Shop'}"></td>
            <td><input type="text" name="blank_label_{$smarty.foreach.blanks.index}" class="form-control" value="{$blank.label|escape:'html':'UTF-8'}"></td>
            <td><input type="number" step="0.01" min="0" name="blank_base_{$smarty.foreach.blanks.index}" class="form-control" value="{$blank.base|escape:'html':'UTF-8'}"></td>
            <td><input type="text" name="blank_note_{$smarty.foreach.blanks.index}" class="form-control" value="{$blank.note|escape:'html':'UTF-8'}"></td>
          </tr>
        {/foreach}
      </tbody>
    </table>

    <hr>
    <h4>{l s='Print methods' d='Modules.Mycustomprint.Shop'}</h4>
    <table class="table">
      <thead>
        <tr>
          <th>{l s='ID' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Label' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Fee' d='Modules.Mycustomprint.Shop'}</th>
          <th>{l s='Note' d='Modules.Mycustomprint.Shop'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$methods item=method name=methods}
          <tr>
            <td><input type="text" name="method_id_{$smarty.foreach.methods.index}" class="form-control" value="{$method.id|escape:'html':'UTF-8'}" placeholder="{l s='auto' d='Modules.Mycustomprint.Shop'}"></td>
            <td><input type="text" name="method_label_{$smarty.foreach.methods.index}" class="form-control" value="{$method.label|escape:'html':'UTF-8'}"></td>
            <td><input type="number" step="0.01" min="0" name="method_fee_{$smarty.foreach.methods.index}" class="form-control" value="{$method.fee|escape:'html':'UTF-8'}"></td>
            <td><input type="text" name="method_note_{$smarty.foreach.methods.index}" class="form-control" value="{$method.note|escape:'html':'UTF-8'}"></td>
          </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="panel-footer">
      <button type="submit" name="submitMyCustomPrintSettings" class="btn btn-default pull-right">
        <i class="process-icon-save"></i> {l s='Save' d='Modules.Mycustomprint.Shop'}
      </button>
    </div>
  </form>
</div>
