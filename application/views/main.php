<!DOCTYPE html>
<meta charset="utf-8">
<link href="<?php echo base_url('public/css/main.css'); ?>" rel="stylesheet"/>
<script src="<?php echo base_url('public/js/jquery.js'); ?>"></script>
<title>爱生活爱记账</title>

<div class="main">
	<h1 class="title">亲爱哒：</h1>
	
	<h4 class="total_balance">您的账户总余额为：<strong><?php echo $total_balance; ?></strong>元。</h4>
	<dl>
	  <dt>其中：</dt>
		<?php foreach($account_arr as $v) {?>
		<dd class="details">
			<span><?php echo $v['account_name']; ?>：</span>
			<span><strong><?php echo $v['balance']; ?>元</strong></span>
			<a href="javascript:;" data_id="<?php echo $v['id']; ?>">查看详情</a>
		</dd>
		<?php } ?>
	</dl>

	<div class="bottom">
		<a id="create_new_account_link" href="javascript:;">创建新账户？</a> |
		<a id="create_new_journal_link" href="javascript:;">快来记账啦</a>
	</div>
</div>

<dialog id="account_add">
    <form class="form" method="post">
        <h4 class="form-title">创建账户</h4>
        <div class="form-item">
        	<label>账户名：</label>
        	<input type="text" name="account_name" id="account_name" placeholder="请输入账户名称" value="" >
    	</div>
    	<div class="form-item">
        	<label>初始金额：</label>
        	<input type="text" name="balance" id="balance" placeholder="0" value="" >
    	</div>
    	<div class="form-item last">
        	<button class="button" type="submit">提交</button>
        	<button class="button" type="button">取消</button>
    	</div>
    </form>
</dialog>

<dialog id="create_new_journal">
	<form class="form" method="post">
	    <h4 class="form-title">创建账目</h4>
	    <div class="form-item">
        	<label>选择账户：</label>
        	<select id="consume_account">
				<option value="0" selected="selected">请选择账户</option>
					<?php foreach ($account_arr as $account){?>
				<option value="<?php echo $account['id']; ?>"><?php echo $account['account_name']; ?></option>
					<?php } ?>
			</select>
    	</div>
    	<div class="form-item"  id="operate_type">
        	<label>交易类型：</label>
        	<input type="radio" name="operate_type" value="1" checked="checked" />收入
			<input type="radio" name="operate_type" value="2" />支出
			<input type="radio" name="operate_type" value="3" />转账
    	</div>
    	<div class="form-item" style="display: none;" id="destination_account">
        	<label>目标账户：</label>
        	<select>
				<option value="0" selected="selected">请选择账户</option>
				<?php foreach ($account_arr as $account){?>
					<option value="<?php echo $account['id']; ?>"><?php echo $account['account_name']; ?></option>
				<?php } ?>
			</select>
    	</div>
		<div class="form-item">
        	<label>交易金额：</label>
        	<input type="text" name="money" value="" id="money" placeholder="" />
    	</div>
    	<div class="form-item">
        	<label>备注：</label>
        	<input type="text" name="desc" value="" id="desc" />
    	</div>
		<div class="form-item last">
	        <button class="button" type="submit">提交</button>
	        <button class="button" type="button">取消</button>
	    </div>
    </form>
</dialog>

<dialog id="journal_list">
	<div class="form-item last" style="margin: 15px auto;">
		<button class="button" type="button">关闭窗口</button>
	</div>
	<div class="form-item">
        	<label>切换账户：</label>
        	<select id="account">
				<option value="0" selected="selected">请选择账户</option>
					<?php foreach ($account_arr as $account){?>
				<option value="<?php echo $account['id']; ?>"><?php echo $account['account_name']; ?></option>
					<?php } ?>
			</select>
    </div>
	<div>
		<table>
			<tr>
				<th>序号</th>
				<th>日期</th>
				<th>交易账户</th>
				<th>交易类型</th>
				<th>交易金额</th>
				<th>对方账户</th>
				<th>备注</th>
			</tr>
		</table>
	</div>
	<div id="pagetext"></div>

</dialog>

<script>
$(function(){
	var Dialog={};
	var href_id; // 实际是指账号id

	// 显示添加账户对话框
	Dialog["AccountAdd"] = $("#account_add");
	$("#create_new_account_link").click(function(){
		return Dialog["AccountAdd"][0].showModal(),false;
	});

	// 创建账户
	Dialog["AccountAdd"].find("button[type=submit]").click(function(){
		var account_name = $('#account_name').val();
		var balance 	 = ($('#balance').val() == '') ? 0 : $('#balance').val();

		if(account_name == ''){
			alert('请输入账户名称');
			return false;
		}

		if(account_name.length > 15){
			alert('您输入的账户名称太长啦');
			return false;
		}

		var path = 'main/create_account';
		var obj = {
				"account_name":account_name,
				"balance":balance
			};
		$.post(path, obj, function(data){
			if(data.code == 0){
				Dialog["AccountAdd"][0].close();
				location.reload(true);
			}
		}, 'json');	
		return false;
	});

	// 关闭添加账户对话框
	Dialog["AccountAdd"].find("button[type=button]").click(function(){
		return Dialog["AccountAdd"][0].close(),false;
	});

	// 显示记账对话框
	Dialog["JournalAdd"] = $("#create_new_journal");
	$("#create_new_journal_link").click(function(){
		return Dialog["JournalAdd"][0].showModal(),false;
	});

	// 根据账户和交易类型判断是否显示目标账户以及可用余额
	$('#consume_account, #operate_type input').click(function(){
		var oprate_type = $('#operate_type input[name=operate_type]:checked').val();
		var accoount_id = $("#consume_account").val();

		// 必须先选账户，然后再选择交易类型；如果选完后改变主意，则清空之前的所有选择
		if(oprate_type != 1 && accoount_id == 0){
			$('#operate_type input[name=operate_type]:checked').attr('checked', false);
			$('#operate_type input[value=1]').attr('checked', 'checked');
			$('#destination_account').hide();
			$('#money').attr('placeholder', '');
			$('#money').val('');
			$('#desc').val('');
			alert('请选择账户');
			return false;
		}

		// 如果是转账类型，则显示目标账户
		if(oprate_type == 3){
			$('#destination_account').show();
		}else{
			$('#destination_account').hide();
		}

		// 如果是支出或者转账，则显示当前可用余额；如果是收入，则不显示当前可用余额
		if(oprate_type == 2 || oprate_type == 3){
			var balance = '0元';
			$('.details a').each(function(index, data){
				if($(this).attr('data_id') == accoount_id){
					balance = $(this).prev().find('strong').text();
				}			
			});
			
			$('#money').attr('placeholder', '当前可用余额'+ balance);
		}else{
			$('#money').attr('placeholder', '');
		}
	});

	// 记账
	Dialog["JournalAdd"].find("button[type=submit]").click(function(){
		var account_id 			= $('#consume_account').val();
		var op_type 			= $('#operate_type input[name=operate_type]:checked').val();
		var destination_account = (op_type == 3) ? $('#destination_account').find('select').val() : null;
		var amount 	 			= $('#money').val();
		var desc 				= $('#desc').val();

		if(account_id == 0){
			alert('请选择账户');
			return false;
		}
		if(op_type == 3 && destination_account == 0){
			alert('请选择目标账户');
			return false;
		}

		if(op_type == 3 && destination_account == account_id){
			alert('同一账户不允许转账哦');
			return false;
		}

		if(amount == ''){
			alert('请填写交易金额');
			return false;
		}
		
		if(desc == 0){
			alert('请填写备注');
			return false;
		}

		var path = 'main/add_journal';
		var obj = {
				"account_id":account_id,
				"op_type":op_type,
				"destination_account":destination_account,
				"amount":amount,
				"desc":desc
			};
		$.post(path, obj, function(data){
			if(data.code == 0){
				Dialog["JournalAdd"][0].close();
				location.reload(true);
			}
		}, 'json');	
		return false;
	});

	// 关闭记账对话框
	Dialog["JournalAdd"].find("button[type=button]").click(function(){
		return Dialog["JournalAdd"][0].close(),false;
	});

	// 点击立即查看获得账目详情
	Dialog["JournalList"] = $("#journal_list");
	$('.details a').click(function(){
		Dialog["JournalList"][0].showModal();

		var id = $(this).attr('data_id');
		var path = 'main/get_journals';
		var obj = {
				"account_id":id
			};

		href_id = id;

		$.post(path, obj, function(data){
			$("table tr").eq(0).nextAll().remove();
			$('#pagetext').remove();
			
			var journal = data.data.journal;
			var pagetext = data.data.pagetext;
			var html = '';
			
			if(journal.length > 0){
				var j = 1;
				for(var i=0;i< journal.length;i++){
					html += "<tr><td>" + j + "</td><td>" + journal[i].create_time +
							"</td><td>" + journal[i].resource_account_name + "</td><td>" + journal[i].op_type +
							"</td><td>" + journal[i].amount + "</td><td>" + journal[i].dest_account_name + 
							"</td><td>" + journal[i].desc + "</td></tr>";
					++j;
				}
				$("table").append(html);
				pagetext = '<div id="pagetext">'+ pagetext + '</div>';
				$('#journal_list').append(pagetext);
			}
		}, 'json');
		return false;
	});

	// 重写的分页监听方法
	Dialog["JournalList"].delegate("#pagetext a", "click", function(){
		var path = this.href;
		var obj = {
				"account_id":href_id
			};

		$.post(path, obj, function(data){
			 $("table tr").eq(0).nextAll().remove();
			 $('#pagetext').remove();
			
			var journal = data.data.journal;
			var pagetext = data.data.pagetext;
			var html = '';
			
			if(journal.length > 0){
				var j = 1;
				for(var i=0;i< journal.length;i++){
					html += "<tr><td>" + j + "</td><td>" + journal[i].create_time +
							"</td><td>" + journal[i].resource_account_name + "</td><td>" + journal[i].op_type +
							"</td><td>" + journal[i].amount + "</td><td>" + journal[i].dest_account_name + 
							"</td><td>" + journal[i].desc + "</td></tr>";
					++j;
				}
				$("table").append(html);
				pagetext = '<div id="pagetext">'+ pagetext + '</div>';
				$('#journal_list').append(pagetext);
			}
		}, 'json');
		return false;
	});

	// 分账户查看账目
	$('#account').change(function(){
		var id = $(this).val();

		if(id == 0){
			return false;
		}
		
		var path = 'main/get_journals';
		var obj = {
				"account_id":id
			};

		href_id = id;

		$.post(path, obj, function(data){
			 $("table tr").eq(0).nextAll().remove();
			 $('#pagetext').remove();
			
			var journal = data.data.journal;
			var pagetext = data.data.pagetext;
			var html = '';
			
			if(journal.length > 0){
				var j = 1;
				for(var i=0;i< journal.length;i++){
					html += "<tr><td>" + j + "</td><td>" + journal[i].create_time +
							"</td><td>" + journal[i].resource_account_name + "</td><td>" + journal[i].op_type +
							"</td><td>" + journal[i].amount + "</td><td>" + journal[i].dest_account_name + 
							"</td><td>" + journal[i].desc + "</td></tr>";
					++j;
				}
				$("table").append(html);
				pagetext = '<div id="pagetext">'+ pagetext + '</div>';
				$('#journal_list').append(pagetext);
			}
		}, 'json');
		return false;
	});

	// 关闭账单对话框
	Dialog["JournalList"].find("button[type=button]").click(function(){
		return Dialog["JournalList"][0].close(),false;
	});
	
});
</script>
