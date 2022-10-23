# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements. See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership. The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied. See the License for the
# specific language governing permissions and limitations
# under the License.
#
FROM wordpress AS wordpress

RUN apt update -y && apt install -y nano && apt install -y zip unzip

RUN curl https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip -o woocommerce.zip

RUN curl https://downloads.wordpress.org/plugin/wc-guatemala.latest-stable.zip -o wc-guatemala.zip

RUN curl https://downloads.wordpress.org/plugin/loco-translate.latest-stable.zip -o loco-translate.zip

RUN curl https://downloads.wordpress.org/plugin/woocommerce-product-generator.latest-stable.zip -o woocommerce-product-generator.zip

RUN curl https://downloads.wordpress.org/plugin/storefront-visual-guide.latest-stable.zip -o storefront-visual-guide.zip

RUN curl https://downloads.wordpress.org/plugin/debug-bar.latest-stable.zip -o debug-bar.zip

RUN curl https://downloads.wordpress.org/theme/storefront.latest-stable.zip -o storefront.zip

RUN unzip woocommerce.zip -d /var/www/html/wp-content/plugins/

RUN unzip wc-guatemala.zip -d /var/www/html/wp-content/plugins/

RUN unzip loco-translate.zip -d /var/www/html/wp-content/plugins/

RUN unzip storefront-visual-guide.zip -d /var/www/html/wp-content/plugins/

RUN unzip woocommerce-product-generator.zip -d /var/www/html/wp-content/plugins/

RUN unzip debug-bar.zip -d /var/www/html/wp-content/plugins/

RUN unzip storefront.zip -d /var/www/html/wp-content/themes/

RUN rm woocommerce.zip wc-guatemala.zip storefront.zip loco-translate.zip woocommerce-product-generator.zip storefront-visual-guide.zip debug-bar.zip

COPY . /var/www/html/wp-content/plugins/wp-p-${PROJECT_NAME:-caex-woocommerce}

EXPOSE 8080