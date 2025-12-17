package src.exo2;

public class Standard extends  Commande{

    

   public Standard() {
    super();
    }

    void setPrice(){
        this.price = 10;
    }

   @Override
   int calculatePrice() {
    setPrice();
    return this.price;
   }

   @Override
   void pay() {
    System.out.println("le client a payee : "+this.price);
   }


}
