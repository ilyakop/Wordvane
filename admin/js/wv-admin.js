/* Wordvane — Admin JS */
/* global wvAdmin, jQuery */

(function($) {
    'use strict';

    /* ── Tooltip System ──────────────────────────────── */
    const Tooltips = {
        flipIfNeeded(wrap) {
            const content = wrap.find('.wv-tooltip-content')[0];
            if (!content) return;
            // getBoundingClientRect forces reflow — accurate after display:block is applied
            wrap.toggleClass('wv-tooltip-below', content.getBoundingClientRect().top < 8);
        },

        init() {
            $(document).on('click', '.wv-tooltip-icon', function(e) {
                e.stopPropagation();
                const wrap = $(this).closest('.wv-tooltip-wrap');
                const isActive = wrap.hasClass('active');
                $('.wv-tooltip-wrap').removeClass('active wv-tooltip-below');
                if (!isActive) {
                    wrap.addClass('active');
                    Tooltips.flipIfNeeded(wrap);
                }
            });

            $(document).on('mouseenter', '.wv-tooltip-wrap', function() {
                Tooltips.flipIfNeeded($(this));
            });

            $(document).on('mouseleave', '.wv-tooltip-wrap', function() {
                $(this).removeClass('wv-tooltip-below');
            });

            $(document).on('click', function() {
                $('.wv-tooltip-wrap').removeClass('active wv-tooltip-below');
            });

            $(document).on('keydown', '.wv-tooltip-icon', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
                if (e.key === 'Escape') {
                    $(this).closest('.wv-tooltip-wrap').removeClass('active wv-tooltip-below');
                }
            });
        }
    };

    /* ── Card Selection (generic radio-style cards) ────── */
    function initCardSelection(cardSelector, activeClass, onSelect) {
        $(document).on('click', cardSelector, function() {
            $(cardSelector).removeClass(activeClass);
            $(this).addClass(activeClass);
            if (onSelect) onSelect($(this).data('value') || $(this).data('type'));
        });
    }

    /* ── Wizard ──────────────────────────────────────── */
    const Wizard = {
        currentStep: 1,
        wizardData: {
            business_type: '',
            business_name: '',
            what_they_sell: '',
            ideal_customer: '',
            main_goal: 'sell',
            products: []
        },

        init() {
            if (!$('.wv-wizard-wrap').length) return;

            initCardSelection('.wv-biz-type-card', 'selected', (val) => {
                this.wizardData.business_type = val;
                $('#wv-step-1 .wv-btn-next').prop('disabled', false);
            });

            $(document).on('click', '.wv-btn-next', (e) => {
                const nextStep = parseInt($(e.currentTarget).data('next'));
                if (this.validateStep(this.currentStep)) {
                    this.collectStep(this.currentStep);
                    this.goToStep(nextStep);
                }
            });

            $(document).on('click', '.wv-btn-back', (e) => {
                const backStep = parseInt($(e.currentTarget).data('back'));
                this.goToStep(backStep);
            });

            $(document).on('click', '#wv-add-product', () => this.addProductRow());
            $(document).on('click', '.wv-remove-product', function() {
                $(this).closest('.wv-product-row').remove();
                if ($('.wv-product-row').length < 3) {
                    $('#wv-add-product').show();
                }
            });

            $(document).on('click', '#wv-complete-wizard', () => this.completeWizard());
        },

        goToStep(step) {
            $('.wv-wizard-step').removeClass('active');
            $('#wv-step-' + step).addClass('active');
            $('.wv-progress-step').removeClass('active done');
            for (let i = 1; i < step; i++) {
                $('[data-step="' + i + '"]').addClass('done');
            }
            $('[data-step="' + step + '"]').addClass('active');
            this.currentStep = step;
        },

        validateStep(step) {
            if (step === 1) {
                if (!this.wizardData.business_type) {
                    alert('Please select a business type to continue.');
                    return false;
                }
            }
            if (step === 2) {
                let valid = true;
                $('#wv-step-2 .wv-required').each(function() {
                    if (!$(this).val().trim()) {
                        $(this).css('border-color', '#cc1818');
                        valid = false;
                    } else {
                        $(this).css('border-color', '');
                    }
                });
                if (!valid) {
                    alert('Please fill in all required fields.');
                    return false;
                }
            }
            return true;
        },

        collectStep(step) {
            if (step === 2) {
                this.wizardData.business_name  = $('#wv-business-name').val().trim();
                this.wizardData.what_they_sell = $('#wv-what-they-sell').val().trim();
                this.wizardData.ideal_customer = $('#wv-ideal-customer').val().trim();
                this.wizardData.main_goal      = $('input[name="wv_main_goal"]:checked').val() || 'sell';
            }
            if (step === 3) {
                this.wizardData.products = [];
                $('.wv-product-row').each((_, el) => {
                    const name = $(el).find('.wv-product-name').val().trim();
                    if (name) {
                        this.wizardData.products.push({
                            name: name,
                            url: $(el).find('.wv-product-url').val().trim(),
                            description: $(el).find('.wv-product-desc').val().trim()
                        });
                    }
                });
            }
        },

        addProductRow() {
            const count = $('.wv-product-row').length;
            if (count >= 3) return;
            const html = `<div class="wv-product-row">
                <div class="wv-product-fields">
                    <input type="text" class="regular-text wv-product-name" placeholder="Product / Service Name">
                    <input type="url" class="regular-text wv-product-url" placeholder="URL on your site">
                    <input type="text" class="regular-text wv-product-desc" placeholder="e.g. Custom leather wallet, hand-stitched, 5 card slots, $89">
                </div>
                <button type="button" class="button wv-remove-product">Remove</button>
            </div>`;
            $('#wv-products-repeater').append(html);
            if ($('.wv-product-row').length >= 3) {
                $('#wv-add-product').hide();
            }
        },

        completeWizard() {
            this.collectStep(3);
            $('#wv-complete-wizard').prop('disabled', true);
            $('#wv-wizard-saving').show();

            $.post(wvAdmin.ajaxurl, {
                action: 'wv_save_wizard',
                nonce: wvAdmin.nonce,
                settings: JSON.stringify(this.wizardData)
            }).done(function(res) {
                if (res.success) {
                    let redirect = res.data.redirect;
                    if (res.data.suggested_keyword) {
                        redirect += '&wv_keyword=' + encodeURIComponent(res.data.suggested_keyword);
                    }
                    window.location.href = redirect;
                } else {
                    alert('Could not save settings. Please try again.');
                    $('#wv-complete-wizard').prop('disabled', false);
                    $('#wv-wizard-saving').hide();
                }
            }).fail(function() {
                alert('Network error. Please try again.');
                $('#wv-complete-wizard').prop('disabled', false);
                $('#wv-wizard-saving').hide();
            });
        }
    };

    /* ── Settings Page ───────────────────────────────── */
    const Settings = {
        init() {
            if (!$('.wv-settings-page').length) return;

            initCardSelection('.wv-biz-type-card', 'selected', (val) => {
                $('#wv-settings-business-type').val(val);
            });

            $(document).on('click', '#wv-add-product-settings', () => {
                const count = $('#wv-products-repeater-settings .wv-product-row').length;
                if (count >= 3) return;
                const html = `<div class="wv-product-row">
                    <div class="wv-product-fields">
                        <input type="text" class="regular-text wv-product-name" placeholder="Product / Service Name">
                        <input type="url" class="regular-text wv-product-url" placeholder="URL on your site">
                        <input type="text" class="regular-text wv-product-desc" placeholder="One-line description">
                    </div>
                    <button type="button" class="button wv-remove-product">Remove</button>
                </div>`;
                $('#wv-products-repeater-settings').append(html);
                if ($('#wv-products-repeater-settings .wv-product-row').length >= 3) {
                    $('#wv-add-product-settings').hide();
                }
            });

            $(document).on('click', '.wv-remove-product', function() {
                $(this).closest('.wv-product-row').remove();
                $('#wv-add-product-settings, #wv-add-product').show();
            });

            $(document).on('click', '#wv-save-dna', () => this.saveDNA());
            $(document).on('click', '#wv-save-api', () => this.savePublishing());
        },

        collectProducts(containerSelector) {
            const products = [];
            $(containerSelector + ' .wv-product-row').each(function() {
                const name = $(this).find('.wv-product-name').val().trim();
                if (name) {
                    products.push({
                        name: name,
                        url: $(this).find('.wv-product-url').val().trim(),
                        description: $(this).find('.wv-product-desc').val().trim()
                    });
                }
            });
            return products;
        },

        showFeedback(message, type) {
            const el = $('#wv-settings-feedback');
            el.show()
                .removeClass('wv-result-success wv-result-error wv-result-warn')
                .addClass('wv-result-' + type)
                .text(message);
            setTimeout(() => el.fadeOut(), 3000);
        },

        saveDNA() {
            const data = {
                action: 'wv_save_settings',
                nonce: wvAdmin.nonce,
                business_type: $('#wv-settings-business-type').val(),
                business_name: $('#wv-s-business-name').val(),
                what_they_sell: $('#wv-s-what-they-sell').val(),
                ideal_customer: $('#wv-s-ideal-customer').val(),
                main_goal: $('input[name="wv_s_main_goal"]:checked').val(),
                brand_voice: $('#wv-s-brand-voice').val(),
                topics_to_avoid: $('#wv-s-topics-avoid').val(),
                locations_served: $('#wv-s-locations').val(),
                products: this.collectProducts('#wv-products-repeater-settings')
            };

            $('#wv-save-spinner').show();
            $.post(wvAdmin.ajaxurl, data).done((res) => {
                $('#wv-save-spinner').hide();
                if (res.success) {
                    this.showFeedback(wvAdmin.strings.saved, 'success');
                } else {
                    this.showFeedback(wvAdmin.strings.save_error, 'error');
                }
            }).fail(() => {
                $('#wv-save-spinner').hide();
                this.showFeedback(wvAdmin.strings.save_error, 'error');
            });
        },

        savePublishing() {
            const data = {
                action: 'wv_save_settings',
                nonce: wvAdmin.nonce,
                seo_plugin: $('input[name="wv_s_seo_plugin"]:checked').val(),
                post_status: $('input[name="wv_s_post_status"]:checked').val(),
                default_category: $('#wv-s-default-cat').val(),
                model_preference: $('#wv-s-model-pref').val().trim()
            };

            $('#wv-save-spinner').show();
            $.post(wvAdmin.ajaxurl, data).done((res) => {
                $('#wv-save-spinner').hide();
                if (res.success) {
                    this.showFeedback(wvAdmin.strings.saved, 'success');
                } else {
                    this.showFeedback(wvAdmin.strings.save_error, 'error');
                }
            }).fail(() => {
                $('#wv-save-spinner').hide();
                this.showFeedback(wvAdmin.strings.save_error, 'error');
            });
        }
    };

    /* ── Generator ───────────────────────────────────── */
    const Generator = {
        currentHtml: '',
        currentMeta: {},
        isGenerating: false,

        init() {
            if (!$('.wv-generator-page').length) return;

            // Pre-fill keyword from wizard
            const params = new URLSearchParams(window.location.search);
            const suggestedKeyword = params.get('wv_keyword');
            if (suggestedKeyword) {
                $('#wv-keyword').val(decodeURIComponent(suggestedKeyword));
            }

            initCardSelection('.wv-article-type-card', 'selected', null);

            $(document).on('click', '#wv-generate-btn', () => this.generate());
            $(document).on('click', '#wv-regenerate', () => this.generate());
            $(document).on('click', '#wv-save-draft', () => this.publish('draft'));
            $(document).on('click', '#wv-publish-now', () => this.publish('publish'));
            $(document).on('click', '#wv-copy-html', () => this.copyHTML());

            this.initCharCounters();
        },

        initCharCounters() {
            function updateCounter(inputId, countId, limit) {
                $(document).on('input', '#' + inputId, function() {
                    const len = $(this).val().length;
                    const counter = $('#' + countId);
                    counter.find('span').text(len);
                    counter.toggleClass('over-limit', len > limit);
                });
            }
            updateCounter('wv-meta-title', 'wv-meta-title-counter', 60);
            updateCounter('wv-meta-description', 'wv-meta-desc-counter', 155);
        },

        getFormData() {
            return {
                keyword:             $('#wv-keyword').val().trim(),
                secondary_keywords:  $('#wv-secondary-keywords').val().trim(),
                article_type:        $('.wv-article-type-card.selected').data('type') || 'how-to',
                featured_product:    $('#wv-featured-product').val(),
                custom_instructions: $('#wv-custom-instructions').val().trim()
            };
        },

        validate(data) {
            if (!data.keyword) {
                $('#wv-keyword-error').show();
                $('#wv-keyword').focus();
                return false;
            }
            $('#wv-keyword-error').hide();
            return true;
        },

        generate() {
            if (this.isGenerating) return;

            const data = this.getFormData();
            if (!this.validate(data)) return;

            this.isGenerating = true;
            $('#wv-generate-btn, #wv-regenerate').prop('disabled', true);
            $('#wv-generator-output-col').show();
            $('#wv-generator-layout').addClass('wv-two-col');
            $('#wv-generating-status').show();
            $('#wv-streaming-output').html('');
            $('#wv-post-generation-panel').hide();
            $('#wv-publish-result').hide();

            $.ajax({
                url: wvAdmin.ajaxurl,
                method: 'POST',
                data: { action: 'wv_generate', nonce: wvAdmin.nonce, ...data },
                timeout: 120000
            }).done((res) => {
                if (res.success) {
                    this.finishGeneration(res.data.text, data.keyword);
                } else {
                    const msg = res.data && res.data.message;
                    if (msg === 'limit_reached') {
                        this.showGenerationError(wvAdmin.strings.limit_reached);
                    } else if (msg === 'no_ai_provider') {
                        this.showGenerationError(wvAdmin.strings.no_ai_provider);
                    } else {
                        this.showGenerationError(wvAdmin.strings.err_generic + (msg || ''));
                    }
                }
            }).fail((xhr, status) => {
                if (status === 'timeout') {
                    this.showGenerationError('Request timed out. The article may be too long — try again.');
                } else {
                    this.showGenerationError(wvAdmin.strings.err_network);
                }
            }).always(() => {
                this.isGenerating = false;
                $('#wv-generate-btn, #wv-regenerate').prop('disabled', false);
                $('#wv-generating-status').hide();
            });
        },

        finishGeneration(fullText, keyword) {
            let articleHtml = fullText.trim();
            let meta = {};

            // 1. Extract JSON — handle both fenced (```json...```) and raw ({...}) output
            const jsonFenceIdx = articleHtml.lastIndexOf('```json');
            if (jsonFenceIdx !== -1) {
                const jsonBodyStart = jsonFenceIdx + 7;
                const closingIdx    = articleHtml.indexOf('```', jsonBodyStart);
                if (closingIdx !== -1) {
                    try {
                        meta = JSON.parse(articleHtml.substring(jsonBodyStart, closingIdx).trim());
                    } catch (e) {}
                }
                articleHtml = articleHtml.substring(0, jsonFenceIdx).trim();
            } else {
                const rawJsonMatch = articleHtml.match(/\n(\{[\s\S]*\})\s*$/);
                if (rawJsonMatch) {
                    try {
                        meta = JSON.parse(rawJsonMatch[1]);
                        articleHtml = articleHtml.substring(0, articleHtml.length - rawJsonMatch[0].length).trim();
                    } catch (e) {}
                }
            }

            // 2. Strip ```html...``` wrapper if present
            if (articleHtml.startsWith('```html')) {
                const end = articleHtml.lastIndexOf('```', articleHtml.length - 1);
                articleHtml = articleHtml.substring(7, end > 7 ? end : undefined).trim();
            } else if (articleHtml.startsWith('```')) {
                const end = articleHtml.lastIndexOf('```', articleHtml.length - 1);
                articleHtml = articleHtml.substring(3, end > 3 ? end : undefined).trim();
            }

            // 3. Extract H1 as post title, remove from content
            const h1Match = articleHtml.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i);
            if (h1Match) {
                $('#wv-post-title').val(h1Match[1].replace(/<[^>]+>/g, '').trim());
                articleHtml = articleHtml.replace(h1Match[0], '').trim();
            }

            this.currentHtml = articleHtml;
            this.currentMeta = meta;

            document.getElementById('wv-streaming-output').innerHTML = articleHtml;

            // Populate meta fields
            if (meta.meta_title)       { $('#wv-meta-title').val(meta.meta_title).trigger('input'); }
            if (meta.meta_description) { $('#wv-meta-description').val(meta.meta_description).trigger('input'); }
            if (meta.slug)             { $('#wv-slug').val(meta.slug); }
            if (meta.tags)             { $('#wv-tags').val(Array.isArray(meta.tags) ? meta.tags.join(', ') : meta.tags); }

            $('#wv-post-generation-panel').show();

            this.updateSEOScore(articleHtml, meta, keyword);
        },

        updateSEOScore(html, meta, keyword) {
            const lowerHtml   = html.toLowerCase();
            const lowerKw     = (keyword || '').toLowerCase();
            const titleText   = ($('#wv-post-title').val() || '').toLowerCase();
            const firstPMatch = html.match(/<p[^>]*>(.*?)<\/p>/i);
            const firstPText  = firstPMatch ? firstPMatch[1].toLowerCase() : '';
            const wordCount   = html.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(w => w.length > 0).length;

            const checks = [
                {
                    pass: lowerKw && titleText.includes(lowerKw),
                    label: 'Keyword found in title',
                    tip: 'The post title should contain your target keyword.'
                },
                {
                    pass: lowerKw && firstPText.includes(lowerKw),
                    label: 'Keyword in first paragraph',
                    tip: 'Google checks the first paragraph for your main keyword.'
                },
                {
                    pass: meta.meta_title && meta.meta_title.length <= 60,
                    label: 'Meta title under 60 chars',
                    tip: 'Titles longer than 60 characters get cut off in search results.'
                },
                {
                    pass: !!meta.meta_description,
                    label: 'Meta description filled in',
                    tip: 'A compelling meta description improves click-through rates.'
                },
                {
                    pass: wordCount > 1000,
                    label: 'Article over 1000 words',
                    tip: 'Longer, detailed articles tend to rank better for competitive keywords.'
                },
                {
                    pass: lowerHtml.includes('<h2') && (lowerHtml.includes('faq') || lowerHtml.includes('question') || lowerHtml.includes('frequently')),
                    label: 'FAQ section present',
                    tip: 'FAQ sections help Google show your content as a featured snippet.'
                }
            ];

            let passCount = 0;
            let html_out = '';
            checks.forEach(function(c) {
                if (c.pass) passCount++;
                const icon = c.pass ? '✅' : '⚠️';
                html_out += `<div class="wv-seo-check-item">
                    <span class="wv-seo-check-icon">${icon}</span>
                    <span>${c.label}
                        <span class="wv-tooltip-wrap">
                            <span class="wv-tooltip-icon" tabindex="0">?</span>
                            <span class="wv-tooltip-content" role="tooltip"><p>${c.tip}</p></span>
                        </span>
                    </span>
                </div>`;
            });

            html_out += `<div class="wv-seo-check-item">
                <span class="wv-seo-check-icon">💡</span>
                <span>Consider adding internal links to other posts manually</span>
            </div>`;

            $('#wv-seo-checklist').html(html_out);

            let grade, gradeClass, gradeTip;
            if (passCount >= 5) {
                grade = 'A'; gradeClass = 'grade-a';
                gradeTip = 'Great — this article is well optimized. Publish it.';
            } else if (passCount >= 3) {
                grade = 'B'; gradeClass = 'grade-b';
                gradeTip = 'Good start. Fill in any missing items before publishing.';
            } else {
                grade = 'C'; gradeClass = 'grade-c';
                gradeTip = 'Needs work. Review the warnings before publishing.';
            }

            $('#wv-grade-badge')
                .attr('class', 'wv-grade-badge ' + gradeClass)
                .attr('title', gradeTip)
                .text(grade);
        },

        showGenerationError(message) {
            $('#wv-generating-status').hide();
            $('#wv-streaming-output').html('<p style="color:#cc1818;">' + message + '</p>');
            this.isGenerating = false;
            $('#wv-generate-btn, #wv-regenerate').prop('disabled', false);
        },

        publish(status) {
            const overrideStatus = $('input[name="wv_publish_status"]:checked').val();
            const postStatus = overrideStatus || status;
            const postId = parseInt($('#wv-post-id').val() || '0');

            const data = {
                action:           'wv_publish_post',
                nonce:            wvAdmin.nonce,
                post_title:       $('#wv-post-title').val(),
                post_content:     this.currentHtml,
                post_status:      postStatus,
                category:         $('#wv-post-category').val(),
                slug:             $('#wv-slug').val(),
                meta_title:       $('#wv-meta-title').val(),
                meta_description: $('#wv-meta-description').val(),
                tags:             $('#wv-tags').val(),
                target_keyword:   $('#wv-keyword').val(),
                faq_schema:       JSON.stringify(this.currentMeta.faq_schema || []),
                post_id:          postId
            };

            const resultEl = $('#wv-publish-result');
            resultEl.hide();
            $('#wv-publish-saving').show();
            $('#wv-save-draft, #wv-publish-now').prop('disabled', true);

            $.post(wvAdmin.ajaxurl, data).done(function(res) {
                $('#wv-publish-saving').hide();
                $('#wv-save-draft, #wv-publish-now').prop('disabled', false);
                if (res.success) {
                    const d = res.data;
                    $('#wv-post-id').val(d.post_id);
                    resultEl.show()
                        .removeClass('wv-result-error')
                        .addClass('wv-result-success')
                        .html(d.message + ' <a href="' + d.edit_link + '" target="_blank">Edit post ↗</a>' +
                            (d.view_link ? ' | <a href="' + d.view_link + '" target="_blank">View post ↗</a>' : ''));
                } else {
                    resultEl.show()
                        .removeClass('wv-result-success')
                        .addClass('wv-result-error')
                        .text((res.data && res.data.message) || 'Could not publish. Please try again.');
                }
            }).fail(function() {
                $('#wv-publish-saving').hide();
                $('#wv-save-draft, #wv-publish-now').prop('disabled', false);
                resultEl.show()
                    .removeClass('wv-result-success')
                    .addClass('wv-result-error')
                    .text('Network error. Please try again.');
            });
        },

        copyHTML() {
            if (!this.currentHtml) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.currentHtml).then(function() {
                    const btn = $('#wv-copy-html');
                    const orig = btn.text();
                    btn.text('✓ Copied!');
                    setTimeout(() => btn.text(orig), 2000);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = this.currentHtml;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
        }
    };

    /* ── Insights Checklist ──────────────────────────── */
    const Insights = {
        init() {
            if (!$('.wv-insights-page').length) return;

            $(document).on('change', '.wv-checklist-cb', function() {
                const checked = [];
                $('.wv-checklist-cb:checked').each(function() {
                    checked.push(parseInt($(this).data('index')));
                });

                $(this).closest('.wv-checklist-item').toggleClass('checked', $(this).is(':checked'));

                const total    = $('.wv-checklist-cb').length;
                const complete = checked.length;
                const pct      = Math.round((complete / total) * 100);
                $('.wv-checklist-fill').css('width', pct + '%');

                $.post(wvAdmin.ajaxurl, {
                    action: 'wv_save_checklist',
                    nonce: wvAdmin.nonce,
                    checklist: checked
                });
            });
        }
    };

    /* ── Init ─────────────────────────────────────────── */
    $(function() {
        Tooltips.init();
        Wizard.init();
        Settings.init();
        Generator.init();
        Insights.init();
    });

})(jQuery);
