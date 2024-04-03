@php
  $geoip = geoip(get_visitor_IP());
  $shipping_country = $business_areas->where('iso_code', $geoip->iso_code)->first();
  $shipping_state = \DB::table('states')
      ->select('id', 'name', 'iso_code')
      ->where([['country_id', '=', $shipping_country->id], ['iso_code', '=', $geoip->state]])
      ->first();
  
  $shipping_zone = get_shipping_zone_of($item->shop_id, $shipping_country->id, optional($shipping_state)->id);
  $shipping_options = isset($shipping_zone->id) ? getShippingRates($shipping_zone->id) : 'NaN';
@endphp

<section>
  <div class="container-fluid">
    <div class="row sc-product-item" id="single-product-wrapper">
      <div class="col-lg-5">
        @include('theme::layouts.jqzoom', ['item' => $item, 'variants' => $variants])
      </div> <!-- /.col-*-5 -->

      <div class="col-lg-7">
        <div class="row mb-4">
          <div class="col-md-7 col-sm-12 p-0">
            <div class="product-single mb-3">
              @include('theme::partials._product_info', ['item' => $item])

              <div class="product-info-options my-4">
                <div class="select-box-wrapper">
                  @foreach ($attributes as $attribute)
                    <div class="row product-attribute">
                      <div class="col-sm-3 col-4">
                        <span class="info-label" id="attr-{{ Str::slug($attribute->name) }}">{{ $attribute->name }}:</span>
                      </div>

                      <div class="col-sm-9 col-8 pl-1">
                        <select class="product-attribute-selector {{ $attribute->css_classes }}" id="attribute-{{ $attribute->id }}" required="required">
                          @foreach ($attribute->attributeValues as $option)
                            <option value="{{ $option->id }}" data-color="{{ $option->color ?? $option->value }}" {{ in_array($option->id, $item_attrs) ? 'selected' : '' }}>{{ $option->value }}</option>
                          @endforeach
                        </select>
                        <div class="help-block with-errors"></div>
                      </div><!-- /.col-sm-9 .col-6 -->
                    </div><!-- /.row -->
                  @endforeach
                </div><!-- /.row .select-box-wrapper -->

                {{ Form::hidden('shipping_zone_id', isset($shipping_zone->id) ? $shipping_zone->id : null, ['id' => 'shipping-zone-id']) }}
                {{ Form::hidden('shipping_rate_id', null, ['id' => 'shipping-rate-id']) }}
                {{ Form::hidden('shipto_country_id', $shipping_country->id, ['id' => 'shipto-country-id']) }}
                {{ Form::hidden('shipto_state_id', optional($shipping_state)->id, ['id' => 'shipto-state-id']) }}

                @unless ($item->product->downloadable)
                  <hr class="dotted" />

                  <div id="calculation-section">
                    <div class="row">
                      <div class="col-3">
                        <span class="info-label" data-options="{{ $shipping_options }}" id="shipping-options">@lang('theme.shipping'):</span>
                      </div>

                      <div class="col-9 pl-1">
                        <span id="summary-shipping-cost" data-value="0"></span>
                        <div id="product-info-shipping-detail">
                          <span>{{ strtolower(trans('theme.to')) }}

                            <a id="shipTo" class="ship_to" data-country="{{ $shipping_country->id }}" data-state="{{ optional($shipping_state)->id }}" href="javascript:void(0);" data-toggle="tooltip" data-placement="top" title="{{ trans('theme.change_shipping_location') }}">
                              {{ $shipping_state ? $shipping_state->name : $geoip->country }}
                            </a>

                            {{-- This is important to keep --}}
                            <select id="width_tmp_select">
                              <option id="width_tmp_option"></option>
                            </select>
                          </span>

                          <span class="dynamic-shipping-rates" data-toggle="popover" title="{{ trans('theme.shipping_options') }}">
                            <span id="summary-shipping-carrier"></span>
                            <small class="ml-1 text-success"><i class="fas fa-caret-circle-down"></i></small>
                          </span>
                        </div>
                        <small id="delivery-time"></small>
                      </div><!-- /.col-sm-9 .col-6 -->
                    </div><!-- /.row -->

                    <div class="row">
                      <div class="col-3">
                        <span class="info-label qtt-label">@lang('theme.quantity'):</span>
                      </div>
                      <div class="col-9 pl-0">
                        <div class="product-qty-wrapper">
                          <div class="product-info-qty-item">
                            <button class="product-info-qty product-info-qty-minus">-</button>
                            <input class="product-info-qty product-info-qty-input" data-name="product_quantity" data-min="{{ $item->min_order_quantity }}" data-max="{{ $item->stock_quantity }}" type="text" value="{{ $item->min_order_quantity }}">
                            <button class="product-info-qty product-info-qty-plus">+</button>
                          </div>
                          <span class="available-qty-count">@lang('theme.stock_count', ['count' => $item->stock_quantity])</span>
                        </div>
                      </div><!-- /.col-sm-9 .col-6 -->
                    </div><!-- /.row -->

                    <div class="row" id="order-total-row">
                      <div class="col-3">
                        <span class="info-label">@lang('theme.total'):</span>
                      </div>
                      <div class="col-9 pl-0">
                        <span id="summary-total" class="text-muted">{{ trans('theme.notify.will_calculated_on_select') }}</span>
                      </div><!-- /.col-sm-9 .col-6 -->
                    </div><!-- /.row -->
                  </div>
                @endunless
              </div><!-- /.product-option -->

              <hr class="dotted" />

              <a href="{{ route('direct.checkout', $item->slug) }}" class="btn btn-primary btn-lg" id="buy-now-btn">
                <i class="fal fa-rocket-launch"></i> @lang('theme.button.buy_now')
              </a>

              <a data-link="{{ route('cart.addItem', $item->slug) }}" class="btn btn-lg add-to-card-now-btn sc-add-to-cart">
                <i class="fal fa-shopping-cart"></i> @lang('theme.button.add_to_cart')
              </a>

              @if (is_incevio_package_loaded('comparison'))
                <a href="javascript:void(0);" data-link="{{ route('comparable.add', $item->id) }}" class="btn btn-default btn-lg add-to-product-compare" id="product-compare-btn">
                  <i class="fal fa-balance-scale"></i> @lang('theme.button.compare')
                </a>
              @endif

              @if ($item->product->inventories_count > 1)
                <a href="{{ route('show.offers', $item->product->slug) }}" class="d-none d-inline-block btn btn-link">
                  @lang('theme.view_more_offers', ['count' => $item->product->inventories_count])
                </a>
              @endif
            </div> <!-- /.product-single -->
          </div> <!-- /.col-*-7 -->

          <div class="col-md-5 col-sm-12">
            <div class="product-page-right-section">
              <div class="seller-info mb-3 ml-3">
                <div class="text-muted small mb-1">
                  @lang('theme.sold_by')
                  <a href="javascript:void(0);" data-toggle="modal" data-target="#shopReviewsModal" class="btn-link pull-right">
                    <i class="far fa-store"></i> {{ trans('theme.button.quick_view') }}
                  </a>
                </div>

                <img class="lazy seller-info-logo" src="{{ get_storage_file_url(optional($item->shop->logoImage)->path, 'tiny') }}" data-src="{{ get_storage_file_url(optional($item->shop->logoImage)->path, 'medium') }}" alt="{{ trans('theme.logo') }}">

                <a href="{{ route('show.store', $item->shop->slug) }}" class="seller-info-name">
                  {!! $item->shop->getQualifiedName() !!}
                </a>

                <div class="mt-2">
                  @include('theme::layouts.ratings', ['ratings' => $item->shop->ratings, 'count' => $item->shop->ratings_count, 'shop' => $item->shop])
                </div>
              </div><!-- /.seller-info -->

              {{-- <a data-link="{{ route('cart.addItem', $item->slug) }}" class="btn btn-primary btn-lg btn-block rounded-0 mb-1 sc-add-to-cart">
              <i class="fas fa-shopping-bag"></i> @lang('theme.button.add_to_cart')
            </a> --}}

              {{-- @if ($item->product->inventories_count > 1)
              <a href="{{ route('show.offers', $item->product->slug) }}" class="btn btn-block btn-link btn-sm">
                @lang('theme.view_more_offers', ['count' => $item->product->inventories_count])
              </a>
            @endif --}}

              {{-- @if ($item->product->offers > 1)
					        <a href="{{ route('show.offers', $item->product->slug) }}" class="btn btn-block btn-link btn-sm">
					        	@lang('theme.view_more_offers', ['count' => $item->product->offers])
					        </a>
						@endif --}}

              @if (unserialize($item->key_features))
                <div class="mt-3">
                  <div class="section-title">
                    <h4>{!! trans('theme.section_headings.key_features') !!}</h4>
                  </div>

                  <ul class="key-feature-list" id="item_key_features">
                    @foreach (unserialize($item->key_features) as $key_feature)
                      <li>
                        <i class="fal fa-check-double"></i>
                        <span>{{ $key_feature }}</span>
                      </li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div> <!-- /.product-page-right-section -->
          </div> <!-- /.col-*-5 -->
        </div><!-- /.row -->
      </div> <!-- /.col-md-7 col-sm-12 -->
    </div> <!-- /.row -->
  </div> <!-- /.container-fluid -->
</section>

<section>
  <div class="container-fluid mt-5">
    <div class="row">
      <div class="col-md-{{ $linked_items->count() ? '8' : '12' }}" id="product-desc-section">
        <div role="tabpanel">
          <ul class="nav nav-tabs nav-justified" role="tablist">
            <li role="presentation" class="active">
              <a href="#desc_tab" aria-controls="desc_tab" role="tab" data-toggle="tab" aria-expanded="true">@lang('theme.product_desc')</a>
            </li>

            <li role="presentation">
              <a href="#seller_desc_tab" aria-controls="seller_desc_tab" role="tab" data-toggle="tab" aria-expanded="false">@lang('theme.product_desc_seller')</a>
            </li>

            <li role="presentation">
              <a href="#reviews_tab" aria-controls="reviews_tab" role="tab" data-toggle="tab" aria-expanded="false">@lang('theme.customer_reviews')</a>
            </li>
          </ul> <!-- /.nav-tab -->

          <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade active in" id="desc_tab">
              {!! $item->product->description !!}

              {{-- Hide technical details from configuration --}}
              @unless (config('system_settings.hide_technical_details_on_product_page'))
                <hr class="dotted my-4" />

                <h3>{{ trans('theme.technical_details') }} </h3>

                <table class="table table-striped noborder">
                  <tbody>
                    @if ($item->product->brand)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.brand') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->brand }}</td>
                      </tr>
                    @endif

                    @if ($item->expiry_date)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('pharmacy::lang.expiry_date') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->expiry_date }}</td>
                      </tr>
                    @endif

                    @if ($item->product->model_number)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.model_number') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->model_number }}</td>
                      </tr>
                    @endif

                    @if ($item->product->gtin_type && $item->product->gtin)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ $item->product->gtin_type }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->gtin }}</td>
                      </tr>
                    @endif

                    @if ($item->product->mpn)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.mpn') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->mpn }}</td>
                      </tr>
                    @endif

                    @if ($item->sku)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.sku') }}:</th>
                        <td class="noborder" id="item_sku" style="width: 65%;">{{ $item->sku }}</td>
                      </tr>
                    @endif

                    @if (config('system_settings.show_item_conditions'))
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.condition') }}:</th>
                        <td class="noborder" id="item_condition" style="width: 65%;">
                          {{ $item->condition }}
                          @if ($item->condition_note)
                            <sup data-toggle="tooltip" data-placement="top" title="{{ $item->condition_note }}">
                              <i class="fas fa-question-circle" id="item_condition_note"></i>
                            </sup>
                          @endif
                        </td>
                      </tr>
                    @endif

                    @if (optional($item->product->manufacturer)->name)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.manufacturer') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->manufacturer->name }}</td>
                      </tr>
                    @endif

                    @if ($item->product->origin)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.origin') }}:</th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->origin->name }}</td>
                      </tr>
                    @endif

                    <tr class="noborder">
                      <th class="text-right noborder">{{ trans('theme.availability') }}:</th>
                      <td class="noborder" style="width: 65%;">{{ $item->availability }}</td>
                    </tr>

                    @if ($item->min_order_quantity)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.min_order_quantity') }}:</th>
                        <td class="noborder" id="item_min_order_qtt" style="width: 65%;">{{ $item->min_order_quantity }}</td>
                      </tr>
                    @endif

                    @if ($item->shipping_weight)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.shipping_weight') }}:</th>
                        <td class="noborder" id="item_shipping_weight" style="width: 65%;">{{ $item->shipping_weight . ' ' . config('system_settings.weight_unit') }}</td>
                      </tr>
                    @endif

                    @if ($item->product->created_at)
                      <tr class="noborder">
                        <th class="text-right noborder">{{ trans('theme.first_listed_on', ['platform' => get_platform_title()]) }}
                          :
                        </th>
                        <td class="noborder" style="width: 65%;">{{ $item->product->created_at->toFormattedDateString() }}</td>
                      </tr>
                    @endif
                  </tbody>
                </table>
              @endunless
            </div>

            <div role="tabpanel" class="tab-pane fade" id="seller_desc_tab">
              <div id="seller_seller_desc">
                {!! $item->description !!}
              </div>

              @if ($item->shop->config->show_shop_desc_with_listing)
                @if ($item->description)
                  <hr class="dashes my-4" />
                @endif

                <h3>{{ trans('theme.seller_info') }} </h3>
                {!! $item->shop->description !!}
              @endif

              @if ($item->shop->config->show_refund_policy_with_listing && $item->shop->config->return_refund)
                <hr class="dashes my-4" />

                {{-- <h4 class="mb-4">{{ trans('theme.return_and_refund_policy') }}: </h4> --}}
                {!! $item->shop->config->return_refund !!}
              @endif
            </div>

            <div role="tabpanel" class="tab-pane fade" id="reviews_tab">
              <div class="reviews-tab">
                @forelse($item->latestFeedbacks as $feedback)
                  <p>
                    {{-- <i class="fas fa-user"></i> --}}
                    <span class="review-user-name">
                      {{ optional($feedback->customer)->getName() }}
                    </span>

                    <span class="pull-right small">
                      <b class="text-success">
                        <i class="fal fa-check"></i>
                        @lang('theme.verified_purchase')
                      </b>

                      <span class="text-muted"> | {{ $feedback->created_at->diffForHumans() }}</span>
                    </span>
                  </p>

                  <p class="my-2">{{ $feedback->comment }}</p>

                  @include('theme::layouts.ratings', ['ratings' => $feedback->rating, 'count' => $feedback->ratings_count])

                  @unless ($loop->last)
                    <hr class="dotted" />
                  @endunless
                @empty
                  <p class="lead text-center text-muted my-4">@lang('theme.no_reviews')</p>
                @endforelse
              </div>
            </div>
          </div><!-- /.tab-content -->
        </div><!-- /.tabpanel -->
      </div><!-- /.col-md-9 -->

      @if ($linked_items->count())
        <div class="col-md-4 frequently-bought-section">
          <div class="section-title">
            <h4 class="mb-3">@lang('theme.section_headings.frequently_bought_together')</h4>
          </div>

          <ul class="sidebar-product-list">
            @include('theme::partials._product_vertical', ['products' => $linked_items])
          </ul>
        </div><!-- /.col-md-2 -->
      @endif
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</section>
