{{-- Spam trap: invisible to people (and skipped by keyboard and screen
     readers), but form-filling bots tend to fill every field. A filled-in
     value is rejected by the 'website' => 'prohibited' validation rule. --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="website">Leave this field empty</label>
    <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
</div>
