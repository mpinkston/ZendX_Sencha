(function() {
	var originalGetCallData = Ext.direct.RemotingProvider.prototype.getCallData;
	Ext.override(Ext.direct.RemotingProvider, {
		getCallData: function(t) {
			var defaults = originalGetCallData.apply(this, arguments);
			return Ext.apply(defaults, {
				namespace: this.namespace.APIDesc.namespace
			});
		},		
	    configureFormRequest : function(action, method, form, callback, scope){
	        var me = this,
	            transaction = Ext.create('Ext.direct.Transaction', {
	                provider: me,
	                action: action,
	                method: method.name,
	                args: [form, callback, scope],
	                callback: scope && Ext.isFunction(callback) ? Ext.Function.bind(callback, scope) : callback,
	                isForm: true
	            }),
	            isUpload,
	            params;
	
	        if (me.fireEvent('beforecall', me, transaction, method) !== false) {
	            Ext.direct.Manager.addTransaction(transaction);
	            isUpload = String(form.getAttribute("enctype")).toLowerCase() == 'multipart/form-data';
	            
	            params = {
	            	extNamespace: this.namespace.APIDesc.namespace,
	                extTID: transaction.id,
	                extAction: action,
	                extMethod: method.name,
	                extType: 'rpc',
	                extUpload: String(isUpload)
	            };
	            
	            // change made from typeof callback check to callback.params
	            // to support addl param passing in DirectSubmit EAC 6/2
	            Ext.apply(transaction, {
	                form: Ext.getDom(form),
	                isUpload: isUpload,
	                params: callback && Ext.isObject(callback.params) ? Ext.apply(params, callback.params) : params
	            });
	            me.fireEvent('call', me, transaction, method);
	            me.sendFormRequest(transaction);
	        }
		}
	})
})();