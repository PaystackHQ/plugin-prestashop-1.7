{*
* Paystack
*}
{if isset($gateway_chosen) && $gateway_chosen == 'paystack'}

<form name="custompaymentmethod" id="paystack_form" method="post" action="{$form_url}">
  <input type="hidden" name="currency_payment" value="{$currencies.0.id_currency}" />
  <input type="hidden" name="amounttotal" value="{$total_amount}" />
  <input type="hidden" name="email" value="{$email}" />
  <input type="hidden" name="reference" value="{$reference}" />
</form>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script type="text/javascript">
  var handler = PaystackPop.setup({
    key: '{$key}',
    email: '{$email}',
    amount: '{$total_amount}',
    ref: '{$reference}',
    currency: '{$currency}',
    callback: function(response){
        $( "#paystack_form" ).submit();
    },
    onClose: function(){
       
    }
  });
  handler.openIframe();
</script>
{/if}