FormValidation.formValidation(
    document.getElementById('residentsform'),
    {



        fields: {

            email: {
                validators: {
                 notEmpty: {
                  message: 'Email is required'
                 },
                 emailAddress: {
                  message: 'The value is not a valid email address'
                 }
                }
               },

               name: {
                validators: {
                    notEmpty: {
                        message: 'Please enter a name'
                    },
                    stringLength: {
                        min: 1,
                        max: 20,
                        message: 'Please enter a Frist name'
                    }
                }
            },

            username: {
                validators: {
                    notEmpty: {
                        message: 'Please enter a Last name'
                    },
                    stringLength: {
                        min: 1,
                        max: 20,
                        message: 'Please enter a Last name'
                    }
                }
            },

            password: {
                validators: {
                    notEmpty: {
                        message: 'Address is required'
                    },
                    stringLength: {
                        min: 5,
                        max: 200,
                        message: 'Please enter an Address'
                    }
                }
            },

            password_confirmation: {
                validators: {
                    notEmpty: {
                        message: 'Address is required'
                    },
                    stringLength: {
                        min: 5,
                        max: 200,
                        message: 'Please enter an Address'
                    }
                }
            },

        },

        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            // Bootstrap Framework Integration
            bootstrap: new FormValidation.plugins.Bootstrap(),
            // Validate fields when clicking the Submit button
            submitButton: new FormValidation.plugins.SubmitButton(),
            // Submit the form when all fields are valid
            defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
        }
    }
);