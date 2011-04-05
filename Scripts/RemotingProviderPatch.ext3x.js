(function() {
	var originalGetCallData = Ext.direct.RemotingProvider.prototype.getCallData;
	Ext.override(Ext.direct.RemotingProvider, {
		getCallData: function(t) {
			var defaults = originalGetCallData.apply(this, arguments);
			return Ext.apply(defaults, {
				namespace: this.namespace.APIDesc.namespace
			});
		},

	    doForm : function(c, m, form, callback, scope){
	        var t = new Ext.Direct.Transaction({
	            provider: this,
	            action: c,
	            method: m.name,
	            args:[form, callback, scope],
	            cb: scope && Ext.isFunction(callback) ? callback.createDelegate(scope) : callback,
	            isForm: true
	        });

	        if(this.fireEvent('beforecall', this, t, m) !== false){
	            Ext.Direct.addTransaction(t);
	            var isUpload = String(form.getAttribute("enctype")).toLowerCase() == 'multipart/form-data',
	                params = {
	                    extTID: t.tid,
	                    extAction: c,
	                    extMethod: m.name,
	                    extNamespace: this.namespace.APIDesc.namespace,
	                    extType: 'rpc',
	                    extUpload: String(isUpload)
	                };

	            // change made from typeof callback check to callback.params
	            // to support addl param passing in DirectSubmit EAC 6/2
	            Ext.apply(t, {
	                form: Ext.getDom(form),
	                isUpload: isUpload,
	                params: callback && Ext.isObject(callback.params) ? Ext.apply(params, callback.params) : params
	            });
	            this.fireEvent('call', this, t, m);
	            this.processForm(t);
	        }
	    }
	})
})();