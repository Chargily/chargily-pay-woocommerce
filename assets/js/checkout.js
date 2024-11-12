const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect } = window.wp.element;
const shouldShowPaymentMethods = chargilySettings.show_payment_methods === 'yes';

if (!shouldShowPaymentMethods) {
    const style = document.createElement('style');
    style.innerHTML = `
      .Chargily-option {
         display: none !important;
      }
      .Chargily-option-no-show {
         display: block !important;
      }
      label.Chargily-label-no-show {
         display: flex !important;
         gap: 5px !important;
         display: flex !important;
         justify-content: flex-start !important;
	 
      }
   `;
    document.head.appendChild(style);
} else {
	const style = document.createElement('style');
    style.innerHTML = `
      .Chargily-option-no-show {
         display: none !important;
      }
   `;
    document.head.appendChild(style);
}

(function() {
    let attempts = 0;
    const maxAttempts = 600;
    const intervalTime = 100;

    function chargilyPayDivConstMove() {
        const targetSpan = document.querySelector('#radio-control-wc-payment-method-options-chargily_pay__label');
        const shouldShowPaymentMethods = false;

        if (targetSpan) {
            if (!shouldShowPaymentMethods) {
                const newDiv = document.createElement('div');
                newDiv.className = 'Chargily-option-no-show';
                newDiv.innerHTML = `
                    <label for="chargilyv2_no-show" class="Chargily-label-no-show">
                        <img class="edahabiaCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px;">
                        <img class="cibCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB Card" style="border-radius: 4px;">
                        <img class="appCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/qr-code.svg" alt="QR Code" style="border-radius: 4px;">
                    </label>
                `;
                targetSpan.appendChild(newDiv);
                document.querySelectorAll('.Chargily-option').forEach(option => {
                    option.style.display = 'none';
                });
                document.querySelectorAll('.Chargily-option-no-show').forEach(option => {
                    option.style.display = 'block';
                });
                document.querySelectorAll('label.Chargily-label-no-show').forEach(option => {
                    option.style.display = 'flex';
                    option.style.justifyContent = 'flex-start';
                });
            } else {
				document.querySelectorAll('.Chargily-option-no-show').forEach(option => {
                    option.style.display = 'nono';
                });
            }
            clearInterval(intervalId);
        }
        attempts++;
        if (attempts >= maxAttempts) {
            clearInterval(intervalId);
        }
    }
    const intervalId = setInterval(chargilyPayDivConstMove, intervalTime);
})();

const labels = {
    en: {
        chargilyPay: chargilySettings.title || "Chargily Pay™ (EDAHABIA/CIB)",
        description: chargilySettings.description || "Pay with your EDAHABIA/CIB card",
        edahabia: "EDAHABIA",
        cib: "CIB Card",
        app: "QR Code",
        poweredBy: "provided by ",
        securePayment: "🔒 Secure E-Payment ",
        istestMode: "Test Mode is enabled.",
        TestWarningMessage: "You are in Test Mode but your Test API keys are missing.",
        TestLinkTextWarningMessage: "Enter your Test API keys.",
        LiveWarningMessage: "You are in Live Mode but your Live API keys are missing.",
    },
    ar: {
        chargilyPay: chargilySettings.title || "شارجيلي باي (الذهبية / CIB)",
        description: chargilySettings.description || "ادفع باستخدام بطاقتك الذهبيالبنكية CIB",
        edahabia: "الذهبية",
        cib: "البطاقة البنكية Cib",
        app: "QR Code",
        poweredBy: "بواسطة ",
        securePayment: "🔒 بوابة دفع إلكتروني آمنة ",
        istestMode: "الTest Mode مفعل.",
        TestWarningMessage: "أنت في وضع التجربة ولكن مفاتيح الAPI لوضع التجربة الخاصة بك مفقودة.",
        TestLinkTextWarningMessage: "أدخل مفاتيح الAPI اللوضع التجربة الخاصة بك.",
        LiveWarningMessage: "أنت في وضع Live ولكن مفاتيح الAPI لوضع الLive الخاصه بك مفقودة.",
    },
    fr: {
        chargilyPay: chargilySettings.title || "Chargily Pay™ (EDAHABIA/CIB)",
        description: chargilySettings.description || "Payez avec votre carte EDAHABIA/CIB",
        edahabia: "EDAHABIA",
        cib: "CIB Card",
        app: "QR Code",
        poweredBy: "🔒 Propulsé par",
        securePayment: "Passerelle de paiement électronique sécurisée.",
        istestMode: "Le mode Test est activé.",
        TestWarningMessage: "Vous êtes en Mode Test mais vos clés API de Mode Test sont manquantes.",
        TestLinkTextWarningMessage: "Entrez vos clés API de Mode Test.",
        LiveWarningMessage: "Vous êtes en Mode Live mais vos clés API de Mode Live sont manquantes.",
    },
};

function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == " ") c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

const PaymentMethodContent = () => {
    const [settings, setSettings] = useState({
        testMode: true,
        liveApiKeyPresent: false,
        liveApiSecretPresent: false,
        testApiKeyPresent: false,
        testApiSecretPresent: false,
    });

    const defaultMethod = getCookie("chargily_payment_method") || "EDAHABIA";
    const [paymentMethod, setPaymentMethod] = useState(defaultMethod);

    const lang = document.documentElement.lang;
    const label = labels[lang] || labels.en;

    const edahabiacardcib = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card-cib.svg`;
    const edahabiaCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg`;
    const cibCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/cib-card.svg`;
    const appCardImage = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/qr-code.svg`;
    const chargilyLogo = `${window.location.origin}/wp-content/plugins/chargily-pay/assets/img/logo.svg`;

    useEffect(() => {
        setCookie("chargily_payment_method", paymentMethod, 7);
        const randomVersion = Math.random().toString(36).substring(2, 15);
        const settingsUrl = `${window.location.origin}/wp-content/plugins/chargily-pay/templates/method-v2/chargily_data.json?v=${randomVersion}`;
        fetch(settingsUrl)
            .then((response) => response.json())
            .then((data) => setSettings(data));
    }, [paymentMethod]);

    const onPaymentMethodChange = (event) => {
        setPaymentMethod(event.target.value);
    };

    const renderContent = () => {
        if (settings.testMode) {
            if (!settings.testApiKeyPresent || !settings.testApiSecretPresent) {
                return createElement(
                    "div", {
                        className: ""
                    },
                    createElement("p", {}, label.TestWarningMessage),
                    createElement(
                        "a", {
                            href: "/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay",
                            target: "_blank",
                            style: {
                                color: "black"
                            },
                        },
                        createElement("p", {}, label.TestLinkTextWarningMessage)
                    )
                );
            } else {
                return createElement("div", {
                        className: ""
                    },
                    createElement("div", {}, label.istestMode),
                    createElement(
                        "div", {
                            className: "Chargily-option"
                        },
                        createElement("input", {
                            type: "radio",
                            id: "chargilyv2_edahabia",
                            name: "chargily_payment_method",
                            value: "EDAHABIA",
                            onChange: onPaymentMethodChange,
                            checked: paymentMethod === "EDAHABIA",
                        }),
                        createElement(
                            "label", {
                                htmlFor: "chargilyv2_edahabia",
                                className: "Chargily",
                                "aria-label": label.edahabia,
                            },
                            createElement("span", {
                                style: {
                                    display: "flex",
                                    alignItems: "center"
                                }
                            }),
                            createElement("div", {
                                className: "Chargily-card-text",
                                style: {},
                                bis_skin_checked: 1,
                            }, label.edahabia),
                            createElement("img", {
                                className: "edahabiaCardImage",
                                src: edahabiaCardImage,
                                alt: label.edahabia,
                                style: {
                                    borderRadius: "4px",
                                },
                            })
                        )
                    ),
                    createElement(
                        "div", {
                            className: "Chargily-option"
                        },
                        createElement("input", {
                            type: "radio",
                            id: "chargilyv2_cib",
                            name: "chargily_payment_method",
                            value: "CIB",
                            onChange: onPaymentMethodChange,
                            checked: paymentMethod === "CIB",
                        }),
                        createElement(
                            "label", {
                                htmlFor: "chargilyv2_cib",
                                className: "Chargily",
                                "aria-label": label.cib,
                            },
                            createElement("span", {
                                style: {
                                    display: "flex",
                                    alignItems: "center"
                                }
                            }),
                            createElement("div", {
                                className: "Chargily-card-text",
                                style: {},
                                bis_skin_checked: 1,
                            }, label.cib),
                            createElement("img", {
                                className: "cibCardImage",
                                src: cibCardImage,
                                alt: label.cib,
                                style: {},
                            })
                        )
                    ),
                    createElement(
                        "div", {
                            className: "Chargily-option"
                        },
                        createElement("input", {
                            type: "radio",
                            id: "chargilyv2_app",
                            name: "chargily_payment_method",
                            value: "chargily_app",
                            onChange: onPaymentMethodChange,
                            checked: paymentMethod === "chargily_app",
                        }),
                        createElement(
                            "label", {
                                htmlFor: "chargilyv2_app",
                                className: "Chargily",
                                "aria-label": label.app,
                            },
                            createElement("span", {
                                style: {
                                    display: "flex",
                                    alignItems: "center"
                                }
                            }),
                            createElement("div", {
                                className: "Chargily-card-text",
                                style: {},
                                bis_skin_checked: 1,
                            }, label.app),
                            createElement("img", {
                                className: "appCardImage",
                                src: appCardImage,
                                alt: label.app,
                                style: {},
                            })
                        )
                    ),
                    createElement(
                        "div", {
                            className: "Chargily-logo-z",
                            style: {
                                display: "flex",
                                flexWrap: "nowrap",
                                alignItems: "center",
                                alignContent: "center"
                            }
                        },
                        createElement("p", {}, label.securePayment, label.poweredBy, ),
                        createElement(
                            "a", {
                                className: "chlogo",
                                href: "https://chargily.com/business/pay",
                                target: "_blank",
                                style: {
                                    color: "black"
                                },
                            },
                            createElement("img", {
                                src: chargilyLogo,
                                alt: "chargily",
                                style: {
                                    height: "30px"
                                },
                            })
                        )
                    )
                );
            }
        } else {
            if (!settings.liveApiKeyPresent || !settings.liveApiSecretPresent) {
                return createElement("p", {}, label.LiveWarningMessage);
            }
            return createElement("div", {className: ""},
				createElement(
                    "div", {
                        className: "Chargily-option"
                    },
                    createElement("input", {
                        type: "radio",
                        id: "chargilyv2_edahabia",
                        name: "chargily_payment_method",
                        value: "EDAHABIA",
                        onChange: onPaymentMethodChange,
                        checked: paymentMethod === "EDAHABIA",
                    }),
                    createElement(
                        "label", {
                            htmlFor: "chargilyv2_edahabia",
                            className: "Chargily",
                            "aria-label": label.edahabia,
                        },
                        createElement("span", {
                            style: {
                                display: "flex",
                                alignItems: "center"
                            }
                        }),
                        createElement("div", {
                            className: "Chargily-card-text",
                            style: {},
                            bis_skin_checked: 1,
                        }, label.edahabia),
                        createElement("img", {
                            className: "edahabiaCardImage",
                            src: edahabiaCardImage,
                            alt: label.edahabia,
                            style: {
                                borderRadius: "4px"
                            },
                        })
                    )
                ),
                createElement(
                    "div", {
                        className: "Chargily-option"
                    },
                    createElement("input", {
                        type: "radio",
                        id: "chargilyv2_cib",
                        name: "chargily_payment_method",
                        value: "CIB",
                        onChange: onPaymentMethodChange,
                        checked: paymentMethod === "CIB",
                    }),
                    createElement(
                        "label", {
                            htmlFor: "chargilyv2_cib",
                            className: "Chargily",
                            "aria-label": label.cib,
                        },
                        createElement("span", {
                            style: {
                                display: "flex",
                                alignItems: "center"
                            }
                        }),
                        createElement("div", {
                            className: "Chargily-card-text",
                            style: {},
                            bis_skin_checked: 1,
                        }, label.cib),
                        createElement("img", {
                            className: "cibCardImage",
                            src: cibCardImage,
                            alt: label.cib,
                            style: {},
                        })
                    )
                ),
                createElement(
                    "div", {
                        className: "Chargily-option"
                    },
                    createElement("input", {
                        type: "radio",
                        id: "chargilyv2_app",
                        name: "chargily_payment_method",
                        value: "chargily_app",
                        onChange: onPaymentMethodChange,
                        checked: paymentMethod === "chargily_app",
                    }),
                    createElement(
                        "label", {
                            htmlFor: "chargilyv2_app",
                            className: "Chargily",
                            "aria-label": label.app,
                        },
                        createElement("span", {
                            style: {
                                display: "flex",
                                alignItems: "center"
                            }
                        }),
                        createElement("div", {
                            className: "Chargily-card-text",
                            style: {},
                            bis_skin_checked: 1,
                        }, label.app),
                        createElement("img", {
                            className: "appCardImage",
                            src: appCardImage,
                            alt: label.app,
                            style: {},
                        })
                    )
                ),
                createElement(
                    "div", {
                        className: "Chargily-logo-z",
                        style: {
                            display: "flex",
                            flexWrap: "nowrap",
                            alignItems: "center",
                            alignContent: "center"
                        }
                    },
                    createElement("p", {}, label.securePayment, label.poweredBy, ),
                    createElement(
                        "a", {
                            className: "chlogo",
                            href: "https://chargily.com/business/pay",
                            target: "_blank",
                            style: {
                                color: "black"
                            },
                        },
                        createElement("img", {
                            src: chargilyLogo,
                            alt: "chargily",
                            style: {
                                height: "30px"
                            },
                        })
                    )
                )
            );
        }
    };
    // marginBottom: "-7px"
    return createElement(
        "div", {
            className: "Chargily-container"
        },
        renderContent()
    );
};

const lang = document.documentElement.lang || "en";

const ChargilyPay = {
    name: "chargily_pay",
    label: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
    content: createElement(PaymentMethodContent),
    edit: createElement(PaymentMethodContent),
    canMakePayment: () => true,
    paymentMethodId: "chargily_pay",
    ariaLabel: labels[lang] ? labels[lang].chargilyPay : labels.en.chargilyPay,
    supports: {
        features: ["products"],
    },
};
registerPaymentMethod(ChargilyPay);
